<?php
namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\{Brand,Category,Product};

/** Public catalog metadata only. No order/customer/credential fields. */
class StoreSeo
{
    const ORIGIN='https://footsteponline.com';
    public static function text($value,$limit=165){return mb_substr(trim(preg_replace('/\s+/u',' ',html_entity_decode(strip_tags((string)$value),ENT_QUOTES,'UTF-8'))),0,$limit);}
    public static function index()
    {
        return Cache::remember('store_seo_index_v1',300,function(){
            $products=filter_products(Product::where('published',1))->select('id','slug','brand_id','category_id','updated_at')->orderBy('id')->get();
            return ['products'=>$products->map(function($p){return ['slug'=>$p->slug?:((string)$p->id),'updated_at'=>optional($p->updated_at)->toAtomString()];})->all(),
                'brands'=>Brand::whereIn('id',$products->pluck('brand_id')->filter()->unique())->get(['name','slug'])->toArray(),
                'categories'=>Category::whereIn('id',$products->pluck('category_id')->filter()->unique())->get(['name','slug'])->toArray()];
        });
    }
    public static function listing($categoryId=null,$brandId=null)
    {
        $model=$categoryId?Category::find($categoryId):($brandId?Brand::find($brandId):null);
        $name=$model?$model->getTranslation('name'):'Sneakers & Shoes';
        $guide=[];
        if($model){
            $key=mb_strtolower(trim($model->name));
            $guide=$categoryId ? config('category_seo.'.$key,[]) : ($brandId ? config('brand_seo.'.$key,[]) : []);
        }
        $heading=$model?$name.' Shoes in Bangladesh':'Sneakers & Shoes in Bangladesh';
        return ['heading'=>$heading,'title'=>self::text(optional($model)->meta_title?:($heading.' | Footstep Online'),90),
            'description'=>self::text(optional($model)->meta_description?:('Browse '.$name.' shoes at Footstep Online Bangladesh. Compare current prices, photos and available sizes before ordering with cash on delivery.')),
            'guide'=>$guide, 'intro'=>$guide['intro']??'দাম, ছবি ও উপলব্ধ সাইজ মিলিয়ে পছন্দের জুতা বেছে নিন। অর্ডারের আগে পণ্যের বিবরণ, মান/গ্রেড ও সাইজ গাইড দেখুন।'];
    }
    public static function canonical()
    {
        $path=request()->getPathInfo();$url=self::ORIGIN.($path==='/'?'/':rtrim($path,'/'));
        $page=request('page');if(is_string($page)&&ctype_digit($page)&&(int)$page>1)$url.='?page='.(int)$page;return $url;
    }
    public static function indexable()
    {
        return request()->is('/','brand/*','category/*','brands','categories','page/*','blog','blog/*','size-guide','returnpolicy')
            && !request()->hasAny(['keyword','q','sort_by','min_price','max_price','color','selected_attribute_values','token']);
    }
}
