<?php

namespace App\Services;

use App\Models\{Category, Product, Upload};
use Illuminate\Database\Eloquent\Collection as ModelCollection;
/** Short-lived request data only: admin edits and stock changes are fresh next request. */
class StorefrontData
{
    public static function readOnly()
    {
        if (!app()->bound('request')) {
            return false;
        }
        $r = app('request');
        return !$r->is('admin', 'admin/*') && ($r->isMethod('GET') || $r->is('home/section/*', 'ajax-search'));
    }
    public static function categories()
    {
        return storefront_request_value('category-tree-v1', function () {
            $categories = Category::orderBy('order_level', 'desc')->get()->keyBy('id');
            OptimizedImages::prime($categories->pluck('icon')->merge($categories->pluck('banner'))->filter()->all());
            return $categories;
        });
    }
    public static function brands()
    {
        return storefront_request_value('brand-map-v1', function () {
            $brands = \App\Models\Brand::all()->keyBy('id');
            OptimizedImages::prime($brands->pluck('logo')->filter()->all());
            return $brands;
        });
    }
    public static function primeSettingsImages()
    {
        storefront_request_value('layout-image-prime', function () {
            $ids = [];
            foreach (['header_logo', 'footer_logo', 'site_icon', 'meta_image'] as $key) {
                $ids[] = get_setting($key);
            }
            foreach (['home_slider_images', 'home_banner1_images', 'home_banner2_images', 'home_banner3_images'] as $key) {
                $value = json_decode(get_setting($key) ?: '[]', true);
                if (is_array($value)) {
                    $ids = array_merge($ids, array_slice($value, 0, 100));
                }
            }
            OptimizedImages::prime(array_filter($ids));
            return true;
        });
    }
    public static function children($id)
    {
        return self::categories()->filter(function ($c) use($id) {
            return (int) $c->parent_id === (int) $id;
        })->values();
    }
    public static function descendants($id)
    {
        $visited = [(int) $id => true];
        $queue = [(int) $id];
        $result = [];
        $groups = self::categories()->groupBy('parent_id');
        while ($queue) {
            $parent = array_shift($queue);
            foreach ($groups->get($parent, collect()) as $category) {
                $key = (int) $category->id;
                if (isset($visited[$key])) {
                    continue;
                }
                $visited[$key] = true;
                $result[] = $category;
                $queue[] = $key;
            }
        }
        return collect($result);
    }
    public static function primeProducts($products)
    {
        $items = $products instanceof \Illuminate\Contracts\Pagination\Paginator ? $products->items() : $products;
        $models = collect($items)->filter(function ($p) {
            return $p instanceof Product;
        })->values();
        if ($models->isEmpty()) {
            return;
        }
        (new ModelCollection($models->all()))->loadMissing(['stocks', 'taxes', 'product_translations']);
        OptimizedImages::prime($models->pluck('thumbnail_img')->all());
    }
    public static function primeView($view)
    {
        if (!self::readOnly()) {
            return;
        }
        self::primeSettingsImages();
        $detail = $view->getData()['detailedProduct'] ?? null;
        if ($detail instanceof Product) {
            $gallery = array_slice(explode(',', (string) $detail->photos), 0, 32);
            $gallery[] = $detail->meta_img;
            $gallery[] = $detail->pdf;
            OptimizedImages::prime(array_filter(array_map(function ($value) {
                return trim((string) $value);
            }, $gallery)));
        }
        $products = [];
        $ids = [];
        $visit = function ($value, $depth = 0) use(&$visit, &$products, &$ids) {
            if ($value instanceof Product) {
                $products[$value->id] = $value;
                return;
            }
            if ($value instanceof Category) {
                if ($value->icon) {
                    $ids[] = $value->icon;
                }
                if ($value->banner) {
                    $ids[] = $value->banner;
                }
                return;
            }
            if ($value instanceof \App\Models\Brand) {
                if ($value->logo) {
                    $ids[] = $value->logo;
                }
                return;
            }
            if ($value instanceof \Illuminate\Contracts\Pagination\Paginator) {
                $value = $value->items();
            }
            if ($depth < 3 && (is_array($value) || $value instanceof \Illuminate\Support\Collection)) {
                foreach ($value as $item) {
                    $visit($item, $depth + 1);
                }
            }
        };
        foreach ($view->getData() as $value) {
            $visit($value);
        }
        if ($products) {
            self::primeProducts(array_values($products));
        }
        if ($ids) {
            OptimizedImages::prime($ids);
        }
    }
    public static function relatedProducts(Product $product, $limit = 8)
    {
        if ((int) $product->brand_id < 1) {
            return new ModelCollection();
        }
        return filter_products(Product::where('published', 1)
            ->where('brand_id', $product->brand_id)->where('id', '!=', $product->id))
            ->with(['stocks', 'taxes', 'product_translations', 'brand'])
            ->orderByDesc('created_at')->orderByDesc('id')->limit($limit)->get();
    }

    public static function detailRecommendations(Product $product)
    {
        return storefront_request_value('detail-recommendations-' . $product->id, function () use($product) {
            $top = filter_products(Product::where('user_id', $product->user_id)->orderBy('num_of_sale', 'desc'))->with(['stocks', 'taxes', 'product_translations'])->limit(6)->get();
            $related = self::relatedProducts($product, 10);
            self::primeProducts($top->concat($related));
            return compact('top', 'related');
        });
    }
    public static function todayDeals()
    {
        // Cache only rotation order. Visibility, prices and stock are read fresh.
        $ids = \Illuminate\Support\Facades\Cache::remember('today-deal-order-v3-' . intdiv(time(), 900), 900, function () {
            return filter_products(Product::where('todays_deal', 1))->reorder()->inRandomOrder()->limit(20)->pluck('id')->all();
        });
        $models = filter_products(Product::where('todays_deal', 1)->whereIn('id', $ids))->with(['stocks', 'taxes', 'product_translations'])->get()->keyBy('id');
        $products = collect($ids)->map(function ($id) use($models) {
            return $models->get($id);
        })->filter()->values();
        self::primeProducts($products);
        return $products;
    }
    public static function categoryProducts($id = null)
    {
        return storefront_request_value('category-products-' . (string) $id, function () use($id) {
            $query = filter_products(Product::query());
            if ($id !== null) {
                $ids = \App\Utility\CategoryUtility::children_ids($id);
                $ids[] = $id;
                $query->whereIn('category_id', $ids);
            }
            $products = $query->with(['stocks', 'taxes', 'product_translations'])->latest()->limit(12)->get();
            self::primeProducts($products);
            return $products;
        });
    }
    public static function section($name)
    {
        return PerformanceLog::measure('catalog_data', function () use($name) {
            if ($name === 'home_categories') {
                $ids = json_decode(get_setting('home_categories') ?: '[]', true) ?: [];
                $categories = collect($ids)->map(function ($id) {
                    return self::categories()->get($id);
                })->filter()->values();
                $sections = [];
                foreach ($categories as $category) {
                    $products = get_cached_products($category->id);
                    self::primeProducts($products);
                    $sections[] = ['category' => $category, 'products' => $products];
                }
                return ['homeCategorySections' => $sections];
            }
            if ($name === 'best_sellers') {
                return ['best_selers' => collect()];
            }
            // Single-vendor store has no seller directory.
            $q = filter_products(Product::where('published', 1));
            if ($name === 'new_arrivals') {
                $q->where('featured', 1);
            }
            if ($name === 'best_selling') {
                $q->reorder()->orderByDesc('num_of_sale');
            }
            // Do not cache stock/pricing across requests.
            $products = $q->with(['stocks', 'taxes', 'product_translations'])->orderByDesc('id')->limit($name === 'new_arrivals' ? 12 : 20)->get();
            self::primeProducts($products);
            return [$name === 'new_arrivals' ? 'featured_products' : ($name === 'best_selling' ? 'best_selling_products' : 'featured_productss') => $products];
        });
    }
}
