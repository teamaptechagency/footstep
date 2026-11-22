<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class HomepageSections
{
    const KEY = 'homepage_product_sections_v1';

    public static function defaults()
    {
        $base = ['enabled'=>true,'auto_slide'=>false,'seconds'=>5,'limit'=>12,'brand_id'=>null,'category_id'=>null,'product_ids'=>[]];
        return [
            'new_arrivals'=>array_merge($base,['title'=>'New Arrivals','position'=>1,'source'=>'latest']),
            'running'=>array_merge($base,['title'=>'Running','position'=>2,'source'=>'category','category_id'=>364]),
            'sneakers'=>array_merge($base,['title'=>'Sneakers','position'=>3,'source'=>'category','category_id'=>365]),
            'best_selling'=>array_merge($base,['title'=>'Best Selling Products','position'=>4,'source'=>'best']),
        ];
    }

    public static function settings()
    {
        $saved=json_decode((string)DB::table('business_settings')->where('type',self::KEY)->value('value'),true);
        $sections=[];
        foreach(self::defaults() as $key=>$defaults) $sections[$key]=array_merge($defaults,is_array($saved[$key]??null)?$saved[$key]:[]);
        return $sections;
    }

    public static function models(array $section)
    {
        $query=StorefrontV3Catalog::query();
        $source=$section['source'];
        if(!empty($section['brand_id'])) $query->where('brand_id',(int)$section['brand_id']);
        if($source==='category') $query->where('category_id',(int)$section['category_id']);
        $limit=max(1,min(40,(int)$section['limit']));
        if($source==='manual') {
            $ids=array_slice(array_values(array_unique(array_map('intval',$section['product_ids']))),0,40);
            if(!$ids) return collect();
            $positions=array_flip($ids);
            return $query->whereIn('id',$ids)->get()->sortBy(function($product)use($positions){return $positions[$product->id];})->take($limit)->values();
        }
        if($source==='best') $query->orderByDesc('num_of_sale');
        return $query->orderByDesc('id')->limit($limit)->get();
    }

    public static function publicSections()
    {
        $sections=collect(self::settings())->sortBy('position');$result=[];
        foreach($sections as $key=>$section){
            if(empty($section['enabled'])) continue;
            $items=self::models($section);if($items->isEmpty())continue;
            $href='/shop';
            if(!empty($section['brand_id'])){$brand=DB::table('brands')->where('id',$section['brand_id'])->first();if($brand)$href='/brand/'.$brand->slug;}
            elseif($section['source']==='category')$href='/shop?category='.(int)$section['category_id'];
            elseif($section['source']==='best')$href='/shop?sort=popularity';
            $result[]=['key'=>$key,'title'=>$section['title'],'position'=>(int)$section['position'],'auto_slide'=>(bool)$section['auto_slide'],'seconds'=>max(2,min(60,(int)$section['seconds'])),'href'=>$href,'items'=>StorefrontV3Catalog::cards($items)];
        }
        return $result;
    }
}
