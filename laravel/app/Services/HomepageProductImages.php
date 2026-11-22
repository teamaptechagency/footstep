<?php
namespace App\Services;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HomepageProductImages
{
    public static function select(array $products, int $hour, int $limit=6): array
    {
        usort($products,function($a,$b){return strcmp($b['created_at'],$a['created_at']) ?: ($b['id'] <=> $a['id']);});
        $groups=[];foreach($products as $p){$groups[(string)($p['brand_id']??0)][]=$p;}
        $brands=array_keys($groups);$count=count($brands);if(!$count)return [];
        $rounds=max(1,(int)ceil($count/$limit));$turn=intdiv($hour,$rounds);$start=($hour%$rounds)*$limit;
        $picked=[];$seen=[];
        for($i=0;$i<$count && count($picked)<$limit;$i++){$rows=$groups[$brands[($start+$i)%$count]];$row=$rows[$turn%count($rows)];$picked[]=$row;$seen[$row['id']]=true;}
        if(count($picked)<$limit){foreach($products as $p){if(!isset($seen[$p['id']])){$picked[]=$p;$seen[$p['id']]=true;}if(count($picked)===$limit)break;}}
        usort($picked,function($a,$b){return strcmp($b['created_at'],$a['created_at']) ?: ($b['id'] <=> $a['id']);});return $picked;
    }
    private static function eligible(?array $ids=null): array
    {
        $query=StorefrontV3Catalog::query()->setEagerLoads([])->whereNotNull('thumbnail_img');
        if($ids!==null)$query->whereIn('products.id',$ids);
        $models=$query->orderByDesc('products.created_at')->orderByDesc('products.id')->get(['products.id','products.slug','products.name','products.brand_id','products.thumbnail_img','products.created_at']);
        $files=DB::table('uploads')->whereIn('id',$models->pluck('thumbnail_img'))->pluck('file_name','id');$public=realpath(public_path());$pool=[];
        foreach($models as $p){$name=$files[$p->thumbnail_img]??null;if(!$name||!$public)continue;$path=realpath(public_path($name));if(!$path||strpos($path,$public.DIRECTORY_SEPARATOR)!==0||!is_file($path))continue;
            $pool[]=['id'=>(int)$p->id,'slug'=>(string)$p->slug,'name'=>(string)$p->name,'brand_id'=>(int)$p->brand_id,'thumbnail_id'=>$p->thumbnail_img,'created_at'=>(string)$p->created_at];}
        return $pool;
    }
    public static function publicData(): array
    {
        $hour=intdiv(time(),3600);$key='homepage_product_images_v1:'.$hour;
        $batch=Cache::get($key);
        // Six-row visibility/media validation prevents cached unpublished images surviving the hour.
        if(is_array($batch)){
            $current=self::eligible(array_column($batch['items'],'id'));$valid=count($current)===count($batch['items']);
            OptimizedImages::prime(array_column($current,'thumbnail_id'));$images=[];foreach($current as $p)$images[$p['id']]=OptimizedImages::asset($p['thumbnail_id']);
            foreach($batch['items'] as $p){if(($images[$p['id']]??null)!==$p['image'])$valid=false;}
            if(!$valid){Cache::forget($key);$batch=null;}
        }
        if(!is_array($batch))$batch=Cache::lock($key.':lock',20)->block(5,function()use($key,$hour){return Cache::remember($key,max(1,($hour+1)*3600-time()),function()use($hour){
            $pool=self::eligible();$mobile=self::select($pool,$hour,4);$selected=$mobile;$ids=array_column($selected,'id');$brands=array_column($selected,'brand_id');$candidates=array_merge(self::select($pool,$hour,6),$pool);
            foreach([true,false] as $distinct){foreach($candidates as $p){if(count($selected)>=6)break;if(in_array($p['id'],$ids,true)||($distinct&&in_array($p['brand_id'],$brands,true)))continue;$selected[]=$p;$ids[]=$p['id'];$brands[]=$p['brand_id'];}}
            usort($selected,function($a,$b){return strcmp($b['created_at'],$a['created_at']) ?: ($b['id'] <=> $a['id']);});OptimizedImages::prime(array_column($selected,'thumbnail_id'));
            return ['mobile_ids'=>array_column($mobile,'id'),'hour'=>$hour,'next_change_at'=>($hour+1)*3600000,'items'=>array_map(function($p){return ['id'=>$p['id'],'name'=>$p['name'],'href'=>'/product/'.rawurlencode($p['slug'] ?: (string)$p['id']),'image'=>OptimizedImages::asset($p['thumbnail_id'])];},$selected)];
        });});
        return $batch;
    }
}


