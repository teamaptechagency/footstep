<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\Brand;
use App\Models\FlashDeal;
class BazarStorefront
{
    public static function homeData()
    {
        $bazarCategories = StorefrontData::categories()->where('parent_id', 0)->sort(function ($a, $b) {
            return (int) $b->featured <=> (int) $a->featured ?: (int) $a->order_level <=> (int) $b->order_level;
        })->take(10)->values();
        $bazarBest = collect();
        $bazarBrands = Brand::orderBy('name')->limit(12)->get();
        $bazarDeal = FlashDeal::where('status', 1)->where('featured', 1)->where('start_date', '<=', time())->where('end_date', '>=', time())->with('flash_deal_products')->first();
        $bazarSale = collect();
        if ($bazarDeal) {
            $ids = $bazarDeal->flash_deal_products->pluck('product_id')->unique()->take(20);
            $items = filter_products(Product::where('published', 1)->whereIn('id', $ids))->with(['stocks', 'taxes'])->get()->keyBy('id');
            $bazarSale = $ids->map(function ($id) use($items) {
                return $items->get($id);
            })->filter()->values();
        }
        $bazarSections = [];
        foreach ($bazarCategories->take(4) as $category) {
            $ids = \App\Utility\CategoryUtility::children_ids($category->id);
            $ids[] = $category->id;
            $items = filter_products(Product::where('published', 1)->whereIn('category_id', $ids))->with(['stocks', 'taxes'])->orderByDesc('id')->limit(10)->get();
            if ($items->isNotEmpty()) {
                $bazarSections[] = ['category' => $category, 'products' => $items];
            }
        }
        // Keep recommendations distinct from the category rows already shown above.
        $shownIds = collect($bazarSections)->pluck('products')->flatten(1)->pluck('id')->unique()->values();
        $bazarProducts = filter_products(Product::where('published', 1)->whereNotIn('id', $shownIds))->with(['stocks', 'taxes'])->orderByDesc('num_of_sale')->orderByDesc('id')->limit(15)->get();
        return compact('bazarCategories', 'bazarProducts', 'bazarBest', 'bazarBrands', 'bazarDeal', 'bazarSale', 'bazarSections');
    }
}
