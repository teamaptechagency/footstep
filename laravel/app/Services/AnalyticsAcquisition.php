<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
class AnalyticsAcquisition {
    public static function report($period,$source=null,$productId=null){
        $window=StoreAnalytics::window($period);
        $empty=['rows'=>collect(),'entries'=>collect(),'sources'=>collect(),'choices'=>collect(),'product_map'=>collect(),'source'=>$source?:'','product_id'=>$productId?:''];
        if(!AnalyticsStorage::ready())return $empty;
        $base=DB::table(AnalyticsStorage::TABLE)->where('created_at','>=',$window['start'])->where('created_at','<',$window['end']);
        $sources=(clone $base)->selectRaw('referrer, COUNT(DISTINCT visitor_hash) AS visitors, COUNT(*) AS views, COUNT(DISTINCT product_id) AS products')->groupBy('referrer')->orderByDesc('visitors')->get();
        $ids=(clone $base)->whereNotNull('product_id')->distinct()->pluck('product_id');
        $choices=Product::whereIn('id',$ids)->orderBy('name')->get(['id','name','slug','thumbnail_img']);
        $products=(clone $base)->whereNotNull('product_id');
        if($source)$products->where('referrer',$source);
        if($productId)$products->where('product_id',$productId);
        $rows=$products->selectRaw('referrer, product_id, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors, AVG(active_seconds) AS average')->groupBy('referrer','product_id')->orderByDesc('visitors')->orderByDesc('views')->limit(50)->get();
        // First observed public page for each browser within the selected report period.
        // This is not claimed to be a full cross-device or lifetime customer journey.
        $first=(clone $base)->selectRaw('MIN(id) AS first_id')->groupBy('visitor_hash');
        $entry=DB::table(AnalyticsStorage::TABLE.' AS v')->joinSub($first,'entry',function($join){$join->on('v.id','=','entry.first_id');});
        if($source)$entry->where('v.referrer',$source);
        if($productId)$entry->where('v.product_id',$productId);
        $entries=$entry->selectRaw('v.referrer, v.path_hash, MAX(v.path) AS path, MAX(v.title) AS title, MAX(v.product_id) AS product_id, COUNT(*) AS visitors')->groupBy('v.referrer','v.path_hash')->orderByDesc('visitors')->limit(30)->get();
        $map=$choices->keyBy('id');return ['rows'=>$rows,'entries'=>$entries,'sources'=>$sources,'choices'=>$choices,'product_map'=>$map,'source'=>$source?:'','product_id'=>$productId?:''];
    }
    public static function label($source){
        if($source==='direct')return 'Direct / Unknown';
        if(preg_match('/(^|\.)facebook\.com$/i',$source))return 'Facebook';
        if(preg_match('/(^|\.)instagram\.com$/i',$source))return 'Instagram';
        if(preg_match('/(^|\.)tiktok\.com$/i',$source))return 'TikTok';
        if(preg_match('/(^|\.)google\.[a-z.]+$/i',$source))return 'Google';
        if(preg_match('/(^|\.)bing\.com$/i',$source))return 'Bing';
        return $source;
    }
}
