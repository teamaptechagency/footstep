<?php

namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/** Global backend visibility, independent of roles and public storefront behaviour. */
class BackendModules
{
    const KEY = 'backend_module_visibility_v1';

    public static function catalog()
    {
        return config('backend_modules', []);
    }

    public static function defaults()
    {
        $flags = [];
        foreach (self::catalog() as $key => $module) {
            $flags[$key] = true;
            foreach ($module['features'] as $feature => $definition) {
                $flags[$key . '.' . $feature] = true;
            }
        }
        return $flags;
    }

    public static function normalize($stored)
    {
        $flags = self::defaults();
        if (is_array($stored)) {
            foreach ($flags as $key => $default) {
                if (array_key_exists($key, $stored) && is_bool($stored[$key])) {
                    $flags[$key] = $stored[$key];
                }
            }
        }
        return $flags;
    }

    public static function state()
    {
        if (app()->bound('request') && app('request')->attributes->has('_backend_modules')) {
            return app('request')->attributes->get('_backend_modules');
        }
        $stored = Cache::remember(self::KEY, 300, function () {
            return json_decode(BusinessSetting::where('type', self::KEY)->value('value') ?: '{}', true);
        });
        $flags = self::normalize($stored);
        $state = ['flags' => $flags, 'revision' => hash('sha256', json_encode($flags))];
        if (app()->bound('request')) {
            app('request')->attributes->set('_backend_modules', $state);
        }
        return $state;
    }

    public static function enabled($key)
    {
        $flags = self::state()['flags'];
        $parent = explode('.', $key)[0];
        return ($flags[$parent] ?? true) && ($flags[$key] ?? true);
    }

    public static function hasDisabled()
    {
        return in_array(false, self::state()['flags'], true);
    }

    public static function forget()
    {
        Cache::forget(self::KEY);
        if (app()->bound('request')) {
            app('request')->attributes->remove('_backend_modules');
            app('request')->attributes->remove('_backend_module_paths');
        }
    }

    public static function save(array $input, $revision)
    {
        $defaults = self::defaults();
        if (array_diff_key($input, $defaults) || array_diff_key($defaults, $input)) {
            throw ValidationException::withMessages(['flags' => 'The module list changed. Refresh this page before saving.']);
        }
        $flags = [];
        foreach ($defaults as $key => $unused) {
            if (!in_array($input[$key], [true, false, 0, 1, '0', '1'], true)) {
                throw ValidationException::withMessages(['flags' => 'Invalid module switch value.']);
            }
            $flags[$key] = in_array($input[$key], [true, 1, '1'], true);
        }
        DB::transaction(function () use ($flags, $revision) {
            $row = BusinessSetting::where('type', self::KEY)->lockForUpdate()->first();
            $current = self::normalize($row ? json_decode($row->value, true) : []);
            if (!is_string($revision) || !hash_equals(hash('sha256', json_encode($current)), $revision)) {
                throw ValidationException::withMessages(['flags' => 'Another admin changed these switches. Refresh the page and try again. / অন্য admin পরিবর্তন করেছেন; refresh করুন।']);
            }
            $row = $row ?: new BusinessSetting();
            $row->type = self::KEY;
            $row->value = json_encode($flags);
            $row->save();
        });
        self::forget();
    }

    /** Most specific route rule wins; disabling All Staff must not hide All Admins. */
    public static function keysForRoute($route)
    {
        if (!$route || Str::is('admin.modules*', (string) $route->getName())) {
            return [];
        }
        $best = -1;
        $keys = [];
        foreach (self::catalog() as $module => $definition) {
            foreach ($definition['features'] as $feature => $settings) {
                foreach ($settings['routes'] as $pattern) {
                    if (Str::is($pattern, (string) $route->getName())) {
                        $score = strpos($pattern, '*') === false ? 10000 + strlen($pattern) : strlen(str_replace('*', '', $pattern));
                        if ($score > $best) {
                            $best = $score;
                            $keys = [$module . '.' . $feature];
                        } elseif ($score === $best) {
                            $keys[] = $module . '.' . $feature;
                        }
                    }
                }
            }
        }
        // Shared read helpers require the module, not an unrelated sub-tool.
        if (!$keys && Str::is('platform.images.*', (string) $route->getName())) {
            $keys[] = 'images';
        }
        if (!$keys && Str::is('products.*', (string) $route->getName())) {
            $keys[] = 'products';
        }
        return array_values(array_unique($keys));
    }

    public static function routeAllowed($route)
    {
        foreach (self::keysForRoute($route) as $key) {
            if (!self::enabled($key)) {
                return false;
            }
        }
        return true;
    }

    public static function routeNameAllowed($name)
    {
        return self::routeAllowed(Route::getRoutes()->getByName($name));
    }

    private static function disabledPaths()
    {
        $request = app('request');
        if ($request->attributes->has('_backend_module_paths')) {
            return $request->attributes->get('_backend_module_paths');
        }
        $patterns = [];
        foreach (Route::getRoutes() as $route) {
            if (!self::routeAllowed($route)) {
                $compiled = $route->getCompiled() ?: $route->toSymfonyRoute()->compile();
                $patterns[] = $compiled->getRegex();
            }
        }
        $patterns = array_values(array_unique($patterns));
        $request->attributes->set('_backend_module_paths', $patterns);
        return $patterns;
    }

    public static function urlAllowed($url)
    {
        if (!is_string($url) || $url === '' || $url[0] === '#' || preg_match('~^(javascript|mailto|tel):~i', $url)) {
            return true;
        }
        $host = parse_url($url, PHP_URL_HOST);
        if ($host && !in_array(strtolower($host), array_filter([strtolower(app('request')->getHost()), strtolower((string) parse_url(config('app.url'), PHP_URL_HOST))]), true)) {
            return true;
        }
        $path = parse_url($url, PHP_URL_PATH);
        if (!is_string($path)) {
            return true;
        }
        foreach (self::disabledPaths() as $pattern) {
            if (preg_match($pattern, rawurldecode($path))) {
                return false;
            }
        }
        return true;
    }

    /** Hide disabled admin links/forms and empty menu groups in the server response.
     * Scripts/styles are held verbatim so the HTML parser never rewrites their contents.
     * This runs only for backend HTML and only when a switch is off.
     */
    public static function filterHtml($html)
    {
        if (!self::hasDisabled() || !is_string($html) || $html === '') {
            return $html;
        }
        $blocks = [];
        $nonce = bin2hex(random_bytes(8));
        $protected = preg_replace_callback('~<(script|style)\b[^>]*>[\s\S]*?</\1\s*>~i', function ($m) use (&$blocks, $nonce) {
            $token = '<!--module-block-' . $nonce . '-' . count($blocks) . '-->';
            $blocks[$token] = $m[0];
            return $token;
        }, $html);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        try {
            if (!$dom->loadHTML('<?xml encoding="UTF-8">' . $protected, LIBXML_NONET)) {
                return $html;
            }
            $xpath = new \DOMXPath($dom);
            $remove = [];
            foreach ($xpath->query('//*[@data-module-feature] | //a[@href] | //form[@action] | //*[@data-url or @data-check-url]') as $node) {
                $disabled = $node->hasAttribute('data-module-feature') && !self::enabled($node->getAttribute('data-module-feature'));
                foreach (['href', 'action', 'data-url', 'data-check-url'] as $attribute) {
                    if ($node->hasAttribute($attribute) && !self::urlAllowed($node->getAttribute($attribute))) {
                        $disabled = true;
                    }
                }
                if ($disabled) {
                    $target = $node;
                    if (strtolower($node->nodeName) === 'a' && $node->parentNode && strtolower($node->parentNode->nodeName) === 'li') {
                        $target = $node->parentNode;
                    }
                    $remove[] = $target;
                }
            }
            foreach ($remove as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
            $groups = [];
            foreach ($xpath->query('//li[contains(concat(" ",normalize-space(@class)," ")," aiz-side-nav-item ")][./ul]') as $li) {
                $groups[] = $li;
            }
            foreach (array_reverse($groups) as $li) {
                if ($xpath->query('./ul/li', $li)->length === 0 && $li->parentNode) {
                    $li->parentNode->removeChild($li);
                }
            }
            foreach (iterator_to_array($dom->childNodes) as $child) {
                if ($child->nodeType === XML_PI_NODE) {
                    $dom->removeChild($child);
                }
            }
            return strtr($dom->saveHTML(), $blocks);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }
}
