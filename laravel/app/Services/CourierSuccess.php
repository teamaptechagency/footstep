<?php
namespace App\Services;
use Illuminate\Support\Facades\{Http,Cache,Crypt};
class CourierSuccess {
 public static function settings(){return array_merge(['enabled'=>false,'api_key'=>''],StoreOperations::get('bdcourier_success'));}
 public static function normalize(array $body){
  if(($body['status']??null)!=='success'||!is_array($body['data']??null))throw new \RuntimeException('invalid_response');
  $rows=[];$summary=null;
  foreach($body['data'] as $key=>$row){
   if(!is_array($row))continue;
   foreach(['total_parcel','success_parcel','cancelled_parcel'] as $field)if(!isset($row[$field])||!is_numeric($row[$field])||$row[$field]<0)throw new \RuntimeException('invalid_counts');
   $total=(int)$row['total_parcel'];$delivered=(int)$row['success_parcel'];$cancelled=(int)$row['cancelled_parcel'];
   if($delivered>$total||$cancelled>$total||$delivered+$cancelled>$total)throw new \RuntimeException('invalid_counts');
   $ratio=$total>0?round(100*$delivered/$total,2):null;
   $clean=['key'=>preg_replace('/[^a-z0-9_-]/','',strtolower((string)$key)),'name'=>mb_substr(strip_tags((string)($row['name']??ucfirst($key))),0,60),'total'=>$total,'delivered'=>$delivered,'cancelled'=>$cancelled,'rate'=>$ratio];
   if($key==='summary')$summary=$clean;else $rows[]=$clean;
  }
  if(!$rows&&$summary===null)throw new \RuntimeException('invalid_response');
  // Never combine providers ourselves: records can overlap. Use provider summary if supplied.
  return ['summary'=>$summary,'couriers'=>$rows,'risk'=>self::risk($body),'checked_at'=>now('Asia/Dhaka')->format('d M Y, g:i A'),'source'=>'BD Courier'];
 }
 private static function risk(array $body){
  $clean=function($value,$max=300){return is_scalar($value)?mb_substr(strip_tags((string)$value),0,$max):'';};
  $verdict=is_array($body['risk_verdict']??null)?$body['risk_verdict']:[];
  $level=$verdict['level']??($body['risk_level']??null);
  if(!in_array($level,['safe','low','medium','high','danger'],true))$level=null;
  $reasons=[];foreach(array_slice(is_array($verdict['reasons']??null)?$verdict['reasons']:[],0,8)as $reason){$text=$clean($reason);if($text!=='')$reasons[]=$text;}
  $provided=array_key_exists('reports',$body)&&is_array($body['reports']);$reports=[];
  if($provided)foreach(array_slice($body['reports'],0,20)as $report){
   if(is_string($report)){$reports[]=['description'=>$clean($report,600)];continue;}
   if(!is_array($report))continue;$row=[];
   foreach(['courier','reason','description','message','report','date','created_at']as $key)if(isset($report[$key])&&is_scalar($report[$key]))$row[$key]=$clean($report[$key],600);
   if($row)$reports[]=$row;
  }
  return ['level'=>$level,'label'=>$clean($verdict['label']??'',80),'action'=>$clean($verdict['action']??'',180),'reasons'=>$reasons,'reports_available'=>$provided,'report_count'=>$provided?count($body['reports']):null,'reports'=>$reports,'source'=>'BD Courier','steadfast_direct_verified'=>false];
 }
 public static function lookup($order){
  $settings=self::settings();
  if(!$settings['enabled']||!$settings['api_key'])return ['ok'=>false,'code'=>'not_configured','message'=>'Integrations → BD Courier-এ API key দিয়ে lookup চালু করুন।'];
  if(in_array($order->delivery_status,['delivered','cancelled'],true))return ['ok'=>false,'code'=>'order_closed','message'=>'নতুন order shipping-এর আগে যাচাই করার জন্য এই lookup ব্যবহার করুন।'];
  $address=json_decode($order->shipping_address,true)?:[];$phone=OrderProtection::phone($address['phone']??'');
  if(!$phone)return ['ok'=>false,'code'=>'invalid_phone','message'=>'Invoice-এ valid Bangladesh mobile number দিন।'];
  $key='courier-success:v2:'.hash_hmac('sha256',$phone.'|'.hash('sha256',$settings['api_key']),config('app.key'));
  if($encrypted=Cache::get($key)){try{return ['ok'=>true,'cached'=>true,'report'=>json_decode(Crypt::decryptString($encrypted),true)];}catch(\Throwable $e){Cache::forget($key);}}
  $lock=Cache::lock($key.':lock',20);if(!$lock->get())return ['ok'=>false,'code'=>'busy','message'=>'একটি check চলছে। কয়েক সেকেন্ড পরে চেষ্টা করুন।'];
  try{
   $response=Http::withToken($settings['api_key'])->acceptJson()->asJson()->withOptions(['connect_timeout'=>4,'allow_redirects'=>false])->timeout(12)->post('https://api.bdcourier.com/courier-check',['phone'=>$phone]);
   if(!$response->successful())return ['ok'=>false,'code'=>'provider_http_'.$response->status(),'message'=>$response->status()===429?'BD Courier usage limit হয়েছে। পরে চেষ্টা করুন।':(in_array($response->status(),[401,403],true)?'BD Courier API key বা account access পরীক্ষা করুন।':'BD Courier এখন report দিতে পারছে না। পরে চেষ্টা করুন।')];
   $report=self::normalize($response->json()??[]);Cache::put($key,Crypt::encryptString(json_encode($report)),now()->addMinutes(15));
   return ['ok'=>true,'cached'=>false,'report'=>$report];
  }catch(\Throwable $e){return ['ok'=>false,'code'=>'lookup_unavailable','message'=>'Delivery report পাওয়া যায়নি। Connection বা provider response পরীক্ষা করুন।'];}
  finally{$lock->release();}
 }
}
