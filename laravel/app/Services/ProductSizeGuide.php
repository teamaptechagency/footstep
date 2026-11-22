<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProductSizeGuide
{
    const KEY='product_size_guide_v1';
    public static function measurements(){return ['39'=>24.1,'40'=>24.5,'41'=>25.4,'42'=>25.8,'43'=>26.7,'44'=>27.1,'45'=>27.9];}
    public static function defaults(){return ['title'=>'Size Guide / সাইজ গাইড','intro'=>'দুই পায়ের দৈর্ঘ্য মেপে বড় পায়ের মাপটি ব্যবহার করুন। Brand, model ও জুতার গঠন অনুযায়ী fit আলাদা হতে পারে। অর্ডারের আগে সন্দেহ হলে আমাদের সঙ্গে যোগাযোগ করুন।','image_id'=>null,'youtube_url'=>'','measurements'=>self::measurements(),'instructions_title'=>'সঠিক মাপ নেবেন যেভাবে','video_title'=>'ভিডিও নির্দেশনা','image_caption'=>'বড় করে দেখতে ছবিতে চাপুন ↗','steps'=>['সমতল মেঝেতে কাগজ রেখে তার ওপর সোজা হয়ে দাঁড়ান।','গোড়ালি ও সবচেয়ে লম্বা আঙুলের শেষ অংশ চিহ্নিত করুন।','দুই চিহ্নের দূরত্ব ruler দিয়ে সেন্টিমিটারে মাপুন।','দুই পা মেপে বড় মাপটি নিন। দুই সাইজের মাঝে হলে বড়টির fit যাচাই করুন।'],'fit_tips'=>[],'extra_info'=>''];}
    public static function settings()
    {
        $saved=json_decode((string)DB::table('business_settings')->where('type',self::KEY)->value('value'),true);
        return array_replace(self::defaults(),is_array($saved)?$saved:[]);
    }
    public static function youtubeId($url)
    {
        $parts=parse_url(trim((string)$url));if(!$parts||strtolower($parts['scheme']??'')!=='https')return null;
        $host=strtolower($parts['host']??'');$id='';
        if($host==='youtu.be')$id=trim($parts['path']??'','/');
        elseif(in_array($host,['youtube.com','www.youtube.com','m.youtube.com'],true)){
            parse_str($parts['query']??'',$query);
            if(($parts['path']??'')==='/watch')$id=is_string($query['v']??null)?$query['v']:'';
            elseif(preg_match('~^/(?:shorts|embed)/([a-zA-Z0-9_-]{11})/?$~D',$parts['path']??'',$match))$id=$match[1];
        }
        return preg_match('/^[a-zA-Z0-9_-]{11}$/D',$id)?$id:null;
    }
    public static function publicData()
    {
        $data=self::settings();$path=$data['image_id']?DB::table('uploads')->where('id',$data['image_id'])->value('file_name'):null;
        $data['image_url']=$path?my_asset($path):null;$data['youtube_id']=self::youtubeId($data['youtube_url']);
        $data['reference']=true;foreach(self::measurements() as $size=>$cm){if((float)($data['measurements'][$size]??0)!==$cm)$data['reference']=false;}
        unset($data['image_id'],$data['youtube_url']);return $data;
    }
}
