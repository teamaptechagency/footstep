<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FlashDeal;
use App\Services\StorefrontV3Catalog as Catalog;
use App\Services\StorefrontData;
use App\Services\OptimizedImages;

class StorefrontV3Controller extends Controller
{
    private function output($data)
    {
        return response()->json($data)->header('Cache-Control', 'private, no-store')->header('X-Content-Type-Options', 'nosniff');
    }
    public function site() { return $this->output(Catalog::site()); }
    public function home() { return $this->output(Catalog::home()); }
    public function products(Request $request)
    {
        $input=$request->validate([
            'name'=>'nullable|string|max:100','categories'=>'nullable|regex:/^[0-9,]+$/',
            'sort_key'=>'nullable|in:relevance,new_arrival,popularity,price_low_to_high,price_high_to_low,top_rated',
            'page'=>'nullable|integer|min:1|max:10000','per_page'=>'nullable|integer|min:1|max:24',
            'featured'=>'nullable|in:1','today'=>'nullable|in:1',
        ]);
        $query=Catalog::query();
        if(!empty($input['categories'])){
            $ids=[];
            foreach(array_slice(explode(',',$input['categories']),0,20) as $id){
                $ids[]=(int)$id;foreach(StorefrontData::descendants((int)$id) as $child)$ids[]=(int)$child->id;
            }
            $query->whereIn('category_id',array_unique($ids));
        }
        if(!empty($input['featured']))$query->where('featured',1);
        if(!empty($input['today']))$query->where('todays_deal',1);
        $search=\App\Services\StorefrontProductSearch::resolve($query,$input['name']??'');$query=$search['query'];
        $sort=$input['sort_key']??(!empty($input['name'])?'relevance':'new_arrival');
        $sorts=['popularity'=>['num_of_sale','desc'],'price_low_to_high'=>['unit_price','asc'],'price_high_to_low'=>['unit_price','desc'],'top_rated'=>['rating','desc'],'new_arrival'=>['id','desc']];
        $query->reorder();
        if($sort==='relevance'){
            $phrase=mb_strtolower($search['info']['matched_query']);
            $escaped=str_replace(['\\','%','_'],['\\\\','\\%','\\_'],$phrase);
            $query->orderByRaw('CASE WHEN LOWER(products.name)=? THEN 0 WHEN LOWER(products.name) LIKE ? THEN 1 ELSE 2 END',[$phrase,$escaped.'%'])->orderByDesc('products.id');
        }else{
            $query->orderBy($sorts[$sort][0],$sorts[$sort][1]);if($sort!=='new_arrival')$query->orderByDesc('products.id');
        }
        $page=$query->paginate((int)($input['per_page']??12));
        return $this->output(['data'=>Catalog::cards($page->items()),'meta'=>['current_page'=>$page->currentPage(),'last_page'=>$page->lastPage(),'total'=>$page->total()],'search'=>$search['info']]);
    }

    public function product($key)
    {
        abort_unless(is_string($key) && preg_match('/^[A-Za-z0-9_-]{1,255}$/D', $key), 404);
        $query = Catalog::query()->with('reviews');
        $product = ctype_digit($key) ? $query->whereKey((int) $key)->firstOrFail() : $query->where('slug', $key)->firstOrFail();
        $detail = Catalog::detail($product);
        $detail['related'] = Catalog::cards(StorefrontData::relatedProducts($product, 8));
        $detail['top_selling'] = Catalog::cards(Catalog::query()->where('id', '<>', $product->id)->where('num_of_sale', '>', 0)->orderByDesc('num_of_sale')->limit(5)->get());
        $detail['contact'] = ['phone' => (string) get_setting('contact_phone'), 'facebook' => (string) get_setting('facebook_link'), 'instagram' => (string) get_setting('instagram_link')];
        return $this->output(['data' => [$detail]]);
    }
    public function deals()
    {
        $groups = FlashDeal::displayOrder()->where('status', 1)->where('start_date', '<=', time())->where('end_date', '>=', time())->get();
        OptimizedImages::prime($groups->pluck('banner')->filter()->all());
        return $this->output(['data' => $groups->map(function ($group) {
            return ['id' => (int) $group->id, 'slug' => $group->slug, 'title' => $group->getTranslation('title'), 'date' => (int) $group->end_date, 'banner' => OptimizedImages::asset($group->banner)];
        })->values()->all()]);
    }
    public function dealProducts($key)
    {
        $query = FlashDeal::where('status', 1)->where('start_date', '<=', time())->where('end_date', '>=', time())->with('flash_deal_products');
        $group = ctype_digit((string) $key) ? $query->whereKey((int) $key)->firstOrFail() : $query->where('slug', $key)->firstOrFail();
        $ids = $group->flash_deal_products->pluck('product_id')->unique();
        $products = Catalog::query()->whereIn('id', $ids)->get()->keyBy('id');
        return $this->output(['data' => Catalog::cards($ids->map(function ($id) use ($products) { return $products->get($id); })->filter()->values())]);
    }
}
