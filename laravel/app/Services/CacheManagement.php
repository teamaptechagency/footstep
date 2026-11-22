<?php
namespace App\Services;
use Illuminate\Support\Facades\{Artisan,Cache,Crypt,Http};
use App\Models\BusinessSetting;
class CacheManagement {
 const KEY='cache_management_cloudflare_v1';
 public static function settings(){return json_decode(BusinessSetting::where('type',self::KEY)->value('value')?:'{}',true)?:[];}
 public static function save($zone,$token,$remove){$old=self::settings();$values=['zone_id'=>$zone,'token'=>$remove?null:($token?Crypt::encryptString($token):($old['token']??null))];$row=BusinessSetting::where('type',self::KEY)->first()?:new BusinessSetting();$row->type=self::KEY;$row->value=json_encode($values);$row->save();}
 public static function server($options){
  $results=[];foreach($options as $type){
   if($type==='application'){
    $driver=config('cache.default');if(!in_array($driver,['file','array'],true)){$results[]=['type'=>$type,'ok'=>false,'message'=>'Automatic application flush is limited to this site file cache. Your '.$driver.' store needs a scoped provider-specific purge.'];continue;}
    if($driver==='file'){$path=realpath(config('cache.stores.file.path'));$root=realpath(storage_path());if(!$path||!$root||strpos(str_replace('\\','/',$path).'/',rtrim(str_replace('\\','/',$root),'/').'/')!==0){$results[]=['type'=>$type,'ok'=>false,'message'=>'File cache is outside this site storage; purge blocked.'];continue;}}
   }
   $commands=['application'=>'cache:clear','views'=>'view:clear','config'=>'config:clear','routes'=>'route:clear'];if(!isset($commands[$type]))continue;
   try{$exit=Artisan::call($commands[$type]);$results[]=['type'=>$type,'ok'=>$exit===0,'message'=>$exit===0?'Cleared successfully.':'Command did not complete. Check storage permissions.'];}catch(\Throwable $e){$results[]=['type'=>$type,'ok'=>false,'message'=>'Could not clear this cache. Check hosting permissions.'];}
  }return $results;
 }
 public static function purge($urls=null){
  $s=self::settings();if(empty($s['zone_id'])||empty($s['token']))return ['ok'=>false,'message'=>'Cloudflare is not configured. ISP, router and operating-system DNS caches cannot be cleared from this website.'];
  try{$token=Crypt::decryptString($s['token']);$r=Http::withToken($token)->withOptions(['allow_redirects'=>false,'connect_timeout'=>8])->timeout(20)->post('https://api.cloudflare.com/client/v4/zones/'.$s['zone_id'].'/purge_cache',$urls?['files'=>$urls]:['purge_everything'=>true]);if($r->successful()&&$r->json('success')===true)return ['ok'=>true,'message'=>'Cloudflare accepted the purge request. Edge propagation may take time; ISP/DNS caches were not changed.'];return ['ok'=>false,'message'=>'Cloudflare purge failed (HTTP '.$r->status().'). Check zone, token permissions and rate limits.'];}catch(\Throwable $e){return ['ok'=>false,'message'=>'Cloudflare request failed. Check credentials, HTTPS and outbound network access.'];}
 }
 public static function validateUrls($urls){$host=strtolower((string)parse_url(config('app.url'),PHP_URL_HOST));foreach($urls as $url){$parts=parse_url($url);if(!$parts||!in_array($parts['scheme']??'',['http','https'],true)||strtolower($parts['host']??'')!==$host||isset($parts['user'])||isset($parts['pass']))return false;}return true;}
}
