<?php
namespace App\Services;
use Illuminate\Support\Str;
class AnalyticsTracker {
    public static function staff($user){return $user&&in_array($user->user_type,['admin','staff'],true);}
    public static function bot($agent){return preg_match('/bot|spider|crawl|headless|preview|monitor|lighthouse|pagespeed|facebookexternalhit/i',(string)$agent);}
    public static function payload($request,$product=null){
        if(self::staff($request->user())||self::bot($request->userAgent())||!AnalyticsStorage::ready())return null;
        $name=$request->route()?$request->route()->getName():'';
        if(!in_array($name,['home','search','suggestion.search','products.category','products.brand','product','flash-deals','flash-deal-details','brands','categories','brands.all','categories.all','blog','blog.details','custom-pages.show'],true))return null;
        $path='/'.ltrim($request->path(),'/');
        $title=$product?$product->getTranslation('name'):ucwords(str_replace(['.','-','_'],' ',(string)$name));
        $data=['path'=>Str::limit($path,500,''),'title'=>Str::limit(strip_tags($title?:'Home'),191,''),'product'=>$product?(int)$product->id:null,'expires'=>time()+21600];
        $encoded=rtrim(strtr(base64_encode(json_encode($data,JSON_UNESCAPED_UNICODE)),'+/','-_'),'=');return $encoded.'.'.hash_hmac('sha256',$encoded,config('app.key'));
    }
    public static function decode($token){
        $parts=explode('.',$token);if(count($parts)!==2||!hash_equals(hash_hmac('sha256',$parts[0],config('app.key')),$parts[1]))return null;
        $data=json_decode(base64_decode(strtr($parts[0],'-_','+/'),true)?:'',true);
        if(!is_array($data)||($data['expires']??0)<time()||empty($data['path'])||!is_string($data['path'])||strlen($data['path'])>2000||strpos($data['path'],'/')!==0)return null;
        return $data;
    }
    public static function visitor($cookie){
        $parts=explode('.',(string)$cookie);
        if(count($parts)===2&&preg_match('/^[a-f0-9]{32}$/D',$parts[0])&&hash_equals(hash_hmac('sha256','visitor:'.$parts[0],config('app.key')),$parts[1]))$id=$parts[0];else $id=bin2hex(random_bytes(16));
        return ['hash'=>hash_hmac('sha256','analytics:'.$id,config('app.key')),'cookie'=>$id.'.'.hash_hmac('sha256','visitor:'.$id,config('app.key'))];
    }
}
