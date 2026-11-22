<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BackendMenuOrder
{
    const KEY = 'backend_sidebar_order_v1';

    public static function catalog()
    {
        $request = app('request');
        if ($request->attributes->has('_menu_order_catalog')) {
            return $request->attributes->get('_menu_order_catalog');
        }
        $nodes = config('backend_menu_order', []);
        foreach ($nodes as &$module) {
            foreach ($module['children'] as &$child) {
                if ($child['id'] === 'integrations__steadfast_accounts') {
                    $child['children'] = array_values(array_filter($child['children'], function ($item) {
                        return $item['path'] === '/admin/steadfast-accounts';
                    }));
                    foreach (SteadfastAccounts::listing() as $account) {
                        $child['children'][] = ['id' => $child['id'] . '__account_' . $account['id'], 'label' => $account['label'], 'path' => '/admin/steadfast-accounts/' . $account['id'], 'children' => []];
                    }
                }
            }
            unset($child);
        }
        unset($module);
        $request->attributes->set('_menu_order_catalog', $nodes);
        return $nodes;
    }

    public static function defaults()
    {
        $groups = [];
        $walk = function ($nodes, $parent) use (&$walk, &$groups) {
            $groups[$parent] = array_column($nodes, 'id');
            foreach ($nodes as $node) {
                if ($node['children']) {
                    $walk($node['children'], $node['id']);
                }
            }
        };
        $walk(self::catalog(), 'root');
        return $groups;
    }

    public static function normalize($stored)
    {
        $result = self::defaults();
        foreach ($result as $group => $defaults) {
            $saved = is_array($stored) && isset($stored[$group]) && is_array($stored[$group]) ? $stored[$group] : [];
            $valid = array_values(array_unique(array_filter($saved, function ($id) use ($defaults) {
                return is_string($id) && in_array($id, $defaults, true);
            })));
            $result[$group] = array_merge($valid, array_values(array_diff($defaults, $valid)));
        }
        return $result;
    }

    public static function state()
    {
        $request = app('request');
        if ($request->attributes->has('_menu_order_state')) {
            return $request->attributes->get('_menu_order_state');
        }
        $stored = Cache::remember(self::KEY, 300, function () {
            return json_decode(BusinessSetting::where('type', self::KEY)->value('value') ?: '{}', true);
        });
        $order = self::normalize($stored);
        $state = ['order' => $order, 'revision' => hash('sha256', json_encode($order))];
        $request->attributes->set('_menu_order_state', $state);
        return $state;
    }

    public static function save(array $input, $revision)
    {
        $defaults = self::defaults();
        if (array_diff_key($input, $defaults) || array_diff_key($defaults, $input)) {
            throw ValidationException::withMessages(['menu_order' => 'Menu list changed. Refresh and arrange it again.']);
        }
        foreach ($defaults as $group => $ids) {
            if (isset($input[$group]) && is_array($input[$group])) {
                foreach ($input[$group] as $id) {
                    if (!is_string($id)) {
                        throw ValidationException::withMessages(['menu_order' => 'Invalid menu item.']);
                    }
                }
            }
            if (!isset($input[$group]) || !is_array($input[$group]) || array_keys($input[$group]) !== range(0, count($input[$group]) - 1) || count($input[$group]) !== count($ids) || count(array_unique($input[$group], SORT_REGULAR)) !== count($ids) || array_diff($ids, $input[$group]) || array_diff($input[$group], $ids)) {
                throw ValidationException::withMessages(['menu_order' => 'Only reorder items inside the same menu group. / একই group-এর মধ্যে menu সাজান।']);
            }
        }
        DB::transaction(function () use ($input, $revision) {
            $row = BusinessSetting::where('type', self::KEY)->lockForUpdate()->first();
            $current = self::normalize($row ? json_decode($row->value, true) : []);
            if (!is_string($revision) || !hash_equals(hash('sha256', json_encode($current)), $revision)) {
                throw ValidationException::withMessages(['menu_order' => 'Another admin changed menu order. Refresh before saving.']);
            }
            $row = $row ?: new BusinessSetting();
            $row->type = self::KEY;
            $row->value = json_encode($input);
            $row->save();
        });
        Cache::forget(self::KEY);
        app('request')->attributes->remove('_menu_order_state');
    }

    public static function arrangedCatalog()
    {
        $order = self::state()['order'];
        $sort = function ($nodes, $group) use (&$sort, $order) {
            $map = array_column($nodes, null, 'id');
            $result = [];
            foreach ($order[$group] ?? [] as $id) {
                if (!isset($map[$id])) {
                    continue;
                }
                $node = $map[$id];
                if ($node['children']) {
                    $node['children'] = $sort($node['children'], $id);
                }
                $result[] = $node;
            }
            return $result;
        };
        return $sort(self::catalog(), 'root');
    }

    /** Reorder the small sidebar fragment before it is sent to the browser. */
    public static function render($html)
    {
        $order = self::state()['order'];
        if ($order === self::defaults()) {
            return $html;
        }
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NONET)) {
                return $html;
            }
            $xp = new \DOMXPath($dom);
            $main = $xp->query('//*[@id="main-menu"]')->item(0);
            if (!$main) {
                return $html;
            }
            $paths = function ($node) use (&$paths) {
                $result = $node['path'][0] === '/' ? [$node['path']] : [];
                foreach ($node['children'] as $child) {
                    $result = array_merge($result, $paths($child));
                }
                return $result;
            };
            $reorder = function ($ul, $definitions, $group) use (&$reorder, $paths, $xp, $order) {
                $mapped = [];
                foreach (iterator_to_array($xp->query('./li', $ul)) as $li) {
                    $own = $xp->query('./a', $li)->item(0);
                    $actual = [];
                    foreach ($xp->query('.//a[@href]', $li) as $a) {
                        $actual[] = parse_url($a->getAttribute('href'), PHP_URL_PATH);
                    }
                    foreach ($definitions as $definition) {
                        $match = $definition['children'] ? count(array_intersect($actual, $paths($definition))) > 0 : ($own && parse_url($own->getAttribute('href'), PHP_URL_PATH) === $definition['path']);
                        if (!$match || isset($mapped[$definition['id']])) {
                            continue;
                        }
                        $mapped[$definition['id']] = $li;
                        $li->setAttribute('data-sidebar-order-id', $definition['id']);
                        $children = $xp->query('./ul', $li)->item(0);
                        if ($children && $definition['children']) {
                            $reorder($children, $definition['children'], $definition['id']);
                        }
                        break;
                    }
                }
                // Temporarily detach known items, preserving all unregistered/role-specific items.
                foreach ($mapped as $li) {
                    $ul->removeChild($li);
                }
                $firstUnmapped = $xp->query('./li', $ul)->item(0);
                foreach ($order[$group] ?? [] as $id) {
                    if (isset($mapped[$id])) {
                        $ul->insertBefore($mapped[$id], $firstUnmapped);
                    }
                }
            };
            $reorder($main, self::catalog(), 'root');
            $body = $dom->getElementsByTagName('body')->item(0);
            $result = '';
            foreach ($body->childNodes as $node) {
                $result .= $dom->saveHTML($node);
            }
            return $result;
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
