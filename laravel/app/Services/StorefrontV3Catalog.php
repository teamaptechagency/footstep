<?php

namespace App\Services;

use App\Models\Product;
use App\Models\FlashDeal;
use App\Services\Checkout\ProductQuote;

/** Public-only DTOs. No customer, account credentials or order data are serialized. */
class StorefrontV3Catalog
{
    public static function query()
    {
        return filter_products(Product::where('published', 1))
            ->with(['stocks', 'taxes', 'product_translations', 'brand']);
    }

    public static function cards($products)
    {
        StorefrontData::primeProducts($products);
        return collect($products)->map(function ($product) { return self::card($product); })->values()->all();
    }

    public static function card(Product $product)
    {
        return [
            'id' => (int) $product->id, 'slug' => $product->slug,
            'name' => $product->getTranslation('name'),
            'thumbnail_image' => OptimizedImages::asset($product->thumbnail_img),
            'has_discount' => home_base_price($product, false) != home_discounted_base_price($product, false),
            'stroked_price' => home_base_price($product), 'main_price' => home_discounted_base_price($product),
            'rating' => (float) $product->rating, 'sales' => (int) $product->num_of_sale,
            'link' => route('product', $product->slug),
        ];
    }

    public static function detail(Product $product)
    {
        $photoIds = array_values(array_filter(explode(',', (string) $product->photos)));
        OptimizedImages::prime(array_merge($photoIds, [$product->thumbnail_img, optional($product->brand)->logo]));
        $variants = [];
        foreach ($product->stocks as $stock) {
            $quote = ProductQuote::selection($product, $stock, max(1, (int) $product->min_qty));
            $regularPrice=(float)$stock->price;$regularTax=0;
            foreach($product->taxes as $rule){$regularTax+=$rule->tax_type==='percent'?$regularPrice*$rule->tax/100:$rule->tax;}
            $variants[] = [
                'variant' => (string) $stock->variant, 'stock' => (int) $stock->qty,
                'price' => (float) ($quote['price'] + $quote['tax']),
                'price_string' => format_price($quote['price'] + $quote['tax']),
                'regular_price' => $regularPrice+$regularTax,
            ];
        }
        return array_merge(self::card($product), [
            'photos' => array_map(function ($id) { return ['path' => OptimizedImages::asset($id)]; }, $photoIds),
            'meta_title' => StoreSeo::text($product->meta_title,90), 'meta_description' => StoreSeo::text($product->meta_description),
            'description' => $product->getTranslation('description'), 'video_link' => $product->video_link, 'video_provider' => $product->video_provider,
            'brand' => $product->brand ? ['name' => $product->brand->getTranslation('name'), 'logo' => OptimizedImages::asset($product->brand->logo)] : null,
            'choice_options' => array_map(function ($choice) {
                return ['name' => (string) ($choice['attribute_id'] ?? ''), 'title' => 'Size', 'options' => $choice['values'] ?? []];
            }, json_decode($product->choice_options ?: '[]', true) ?: []),
            'addons' => ProductAddons::available($product->id),
            'variants' => $variants, 'min_qty' => max(1, (int) $product->min_qty), 'unit' => $product->unit,
            'made_in' => ProductOrigin::value($product->made_in),
            'comfort_note' => ProductComfort::value($product->comfort_note),
            'stock_visibility_state' => in_array($product->stock_visibility_state, ['quantity', 'text', 'hide'], true) ? $product->stock_visibility_state : 'hide',
            'reviews' => $product->reviews->map(function ($review) {
                return ['id' => (int) $review->id, 'imported' => $review->is_manual, 'name' => $review->display_name, 'rating' => (float) $review->rating, 'comment' => $review->comment, 'date' => optional($review->created_at)->format('d M Y')];
            })->values()->all(),
            'rating_count' => $product->reviews->count(),
        ]);
    }

    public static function categories()
    {
        return StorefrontData::categories()->map(function ($category) {
            return ['id' => (int) $category->id, 'name' => $category->getTranslation('name'), 'slug' => $category->slug,
                'icon' => OptimizedImages::asset($category->icon), 'banner' => OptimizedImages::asset($category->banner)];
        })->values()->all();
    }

    public static function site()
    {
        StorefrontData::primeSettingsImages();
        $array = function ($key) { return json_decode(get_setting($key) ?: '[]', true) ?: []; };
        $labels = $array('header_menu_labels'); $links = $array('header_menu_links'); $menu = [];
        foreach ($labels as $index => $label) {
            if (!in_array(strtolower(trim(strip_tags($label))), ['facebook page', 'contract'], true)) {
                $menu[] = ['label' => strip_tags($label), 'href' => $links[$index] ?? '/'];
            }
        }
        $banners = [];
        foreach ([1, 2, 3] as $number) {
            $urls = $array('home_banner'.$number.'_links'); $banners[$number] = [];
            foreach ($array('home_banner'.$number.'_images') as $index => $id) {
                $banners[$number][] = ['image' => OptimizedImages::asset($id), 'href' => $urls[$index] ?? '/search'];
            }
        }
        $categories = StorefrontData::categories(); $brands = StorefrontData::brands();
        return [
            'layout' => StorefrontAppearance::settings()['layout'], 'palette' => StorefrontAppearance::palette(),
            'logo' => OptimizedImages::asset(get_setting('header_logo')),
            'footerLogo' => OptimizedImages::asset(get_setting('footer_logo') ?: get_setting('header_logo')),
            'menu' => $menu, 'homeCategories' => $array('home_categories'), 'sliderLinks' => $array('home_slider_links'), 'banners' => $banners,
            'copyright' => trim(strip_tags(get_setting('frontend_copyright_text', null, app()->getLocale()))),
            'about' => strip_tags(get_setting('about_us_description', null, app()->getLocale())),
            'contactPhone' => get_setting('contact_phone'), 'facebook' => get_setting('facebook_link'), 'instagram' => get_setting('instagram_link'),
            'bestSelling' => get_setting('best_selling') == 1,
            'topCategories' => collect($array('top10_categories'))->map(function ($id) use ($categories) {
                $c = $categories->get($id); return $c ? ['id' => (int) $c->id, 'name' => $c->getTranslation('name'), 'image' => OptimizedImages::asset($c->banner)] : null;
            })->filter()->values()->all(),
            'topBrands' => collect($array('top10_brands'))->map(function ($id) use ($brands) {
                $b = $brands->get($id); return $b ? ['id' => (int) $b->id, 'name' => $b->getTranslation('name'), 'image' => OptimizedImages::asset($b->logo), 'slug' => $b->slug] : null;
            })->filter()->values()->all(),
            'categories' => self::categories(),
        ];
    }

    public static function home()
    {
        $site = self::site();
        $new = StorefrontData::section('new_arrivals')['featured_products'];
        $best = StorefrontData::section('best_selling')['best_selling_products'];
        $all = StorefrontData::categoryProducts();
        $sections = StorefrontData::section('home_categories')['homeCategorySections'];
        $sliders = json_decode(get_setting('home_slider_images') ?: '[]', true) ?: [];
        return ['site' => $site, 'latest' => self::cards($new), 'best' => self::cards($best), 'products' => self::cards($all),
            'today' => self::cards(StorefrontData::todayDeals()),
            'sliders' => array_map(function ($id) { return ['photo' => OptimizedImages::asset($id)]; }, $sliders),
            'categorySections' => array_map(function ($section) {
                return ['category' => ['id' => (int) $section['category']->id, 'name' => $section['category']->getTranslation('name')], 'products' => self::cards($section['products'])];
            }, $sections),
        ];
    }
}
