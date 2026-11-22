<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class HomepageBanners
{
    const KEY = 'homepage_banners_v1';
    public static function settings()
    {
        $raw=DB::table('business_settings')->where('type',self::KEY)->value('value');
        if($raw!==null){$data=json_decode($raw,true);if(is_array($data))return $data;}
        $images=json_decode((string)get_setting('home_slider_images'),true)?:[];
        $links=json_decode((string)get_setting('home_slider_links'),true)?:[];
        $items=[];
        foreach(array_slice($images,0,30) as $i=>$id){$items[]=['id'=>'legacy-'.($i+1),'title'=>'Banner '.($i+1),'image_id'=>(int)$id,'mobile_image_id'=>null,'href'=>self::safeLink($links[$i]??'/shop'),'alt'=>'Footstep collection','position'=>$i+1,'enabled'=>true,'starts_at'=>null,'ends_at'=>null,'new_tab'=>false];}
        return ['auto_slide'=>true,'seconds'=>5,'items'=>$items];
    }
    public static function safeLink($link)
    {
        $link=trim((string)$link);
        if(preg_match('~^/(?!/)[^\x00-\x20\\\\]*$~D',$link))return $link;
        if(filter_var($link,FILTER_VALIDATE_URL)&&in_array(strtolower(parse_url($link,PHP_URL_SCHEME)),['http','https'],true))return $link;
        return '/shop';
    }
    public static function status(array $item,$at=null)
    {
        $at=$at?:Carbon::now('Asia/Dhaka');
        if(empty($item['enabled']))return 'Disabled';
        if(!empty($item['starts_at'])&&$at->lt(Carbon::parse($item['starts_at'],'Asia/Dhaka')))return 'Scheduled';
        if(!empty($item['ends_at'])&&$at->gte(Carbon::parse($item['ends_at'],'Asia/Dhaka')))return 'Expired';
        return 'Live';
    }
    public static function mutate(callable $change)
    {
        return Cache::lock('homepage-banners-write',10)->block(3,function()use($change){return DB::transaction(function()use($change){
            DB::table('business_settings')->where('type',self::KEY)->lockForUpdate()->first();
            $data=$change(self::settings());
            DB::table('business_settings')->updateOrInsert(['type'=>self::KEY],['value'=>json_encode($data,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]);
            return $data;
        });});
    }
    public static function publicData()
    {
        $settings=self::settings();$items=collect($settings['items'])->filter(function($item){return self::status($item)==='Live';})->sortBy(function($item){return sprintf('%05d:%s',$item['position'],$item['id']);});
        $ids=$items->flatMap(function($item){return [$item['image_id'],$item['mobile_image_id']??null];})->filter()->unique();
        $uploads=DB::table('uploads')->whereIn('id',$ids)->get(['id','file_name'])->keyBy('id');
        $slides=[];
        foreach($items as $item){$image=$uploads->get($item['image_id']);if(!$image)continue;$mobile=$uploads->get($item['mobile_image_id']??0);
            $slides[]=['id'=>$item['id'],'image'=>my_asset($image->file_name),'mobile_image'=>$mobile?my_asset($mobile->file_name):null,'href'=>self::safeLink($item['href']),'alt'=>$item['alt']?:$item['title'],'new_tab'=>(bool)($item['new_tab']??false)];}
        return ['slides'=>$slides,'auto_slide'=>(bool)$settings['auto_slide'],'seconds'=>(int)$settings['seconds'],'timezone'=>'Asia/Dhaka'];
    }
}
