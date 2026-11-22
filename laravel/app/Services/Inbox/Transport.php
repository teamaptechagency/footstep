<?php
namespace App\Services\Inbox;
use Illuminate\Support\Facades\Http;
class Transport {
 public static function credentials($b,$ch){return Store::unpack($ch->credentials);}
 public static function send($b,$ch,$c,$m){
  if($b->mock)return ['message_id'=>'mock-'.bin2hex(random_bytes(12))];
  if(!$b->live_enabled)throw new \RuntimeException('live_sending_disabled');
  $token=self::credentials($b,$ch)['access_token']??null;if(!$token)throw new \RuntimeException('meta_token_missing');
  $remote=Store::unpack($c->remote_secret);$payload=Store::unpack($m->payload);$text=Store::unpack($m->body);
  if(!is_string($remote)||!preg_match('/^[0-9]{5,40}$/D',$remote))throw new \RuntimeException('real_recipient_required');
  if($ch->type==='whatsapp'){
   $data=['messaging_product'=>'whatsapp','recipient_type'=>'individual','to'=>$remote];
   if($m->kind==='template')$data+=['type'=>'template','template'=>$payload['template']];
   elseif($m->kind==='image')$data+=['type'=>'image','image'=>['link'=>$payload['image'],'caption'=>mb_substr($text,0,1000)]];
   else $data+=['type'=>'text','text'=>['body'=>$text,'preview_url'=>true]];
  }else{
   $message=$m->kind==='image'?['attachment'=>['type'=>'image','payload'=>['url'=>$payload['image'],'is_reusable'=>true]]]:['text'=>$text];
   $data=['recipient'=>['id'=>$remote],'messaging_type'=>'RESPONSE','message'=>$message];
  }
  try{$response=Http::withToken($token)->withOptions(['allow_redirects'=>false,'connect_timeout'=>8])->timeout(20)->post('https://graph.facebook.com/'.$b->graph_version.'/'.$ch->external_id.'/messages',$data);}catch(\Throwable $e){throw new \RuntimeException('send_uncertain_transport');}
  if($response->status()===429)throw new \RuntimeException('retry_meta_429');
  if($response->serverError()||$response->status()===408)throw new \RuntimeException('send_uncertain_http_'.$response->status());
  if(!$response->successful())throw new \RuntimeException('meta_http_'.$response->status().'_'.(int)$response->json('error.code'));
  $id=$response->json($ch->type==='whatsapp'?'messages.0.id':'message_id');if(!is_string($id)||!$id)throw new \RuntimeException('send_uncertain_missing_id');return ['message_id'=>$id];
 }
 public static function check($b,$ch){
  if($b->mock)return 'Mock connection ready. No external API called.';
  $token=self::credentials($b,$ch)['access_token']??'';if(!$token)return 'Token missing.';
  try{$response=Http::withToken($token)->withOptions(['allow_redirects'=>false,'connect_timeout'=>8])->timeout(15)->get('https://graph.facebook.com/'.$b->graph_version.'/'.$ch->external_id,['fields'=>'id']);return $response->successful()&&(string)$response->json('id')===$ch->external_id?'Identity checked. Sending permissions and webhook subscription must still be verified in Meta.':'Connection failed: HTTP '.$response->status().'. Check token, ID and permissions.';}catch(\Throwable $e){return 'Connection failed: network or TLS error.';}
 }
 public static function template($b,$ch,$name,$language){
  if($b->mock)return ['name'=>$name,'language'=>['code'=>$language]];
  if(!$ch->waba_id)throw new \RuntimeException('waba_id_missing');
  try{$response=Http::withToken(self::credentials($b,$ch)['access_token']??'')->withOptions(['allow_redirects'=>false,'connect_timeout'=>8])->timeout(15)->get('https://graph.facebook.com/'.$b->graph_version.'/'.$ch->waba_id.'/message_templates',['name'=>$name,'fields'=>'name,status,language,components','limit'=>100]);}catch(\Throwable $e){throw new \RuntimeException('template_check_failed');}
  if(!$response->successful())throw new \RuntimeException('template_check_failed');
  foreach($response->json('data',[])as $t){if($t['name']!==$name||$t['language']!==$language||$t['status']!=='APPROVED')continue;$raw=json_encode($t['components']??[]);if(strpos($raw,'{{')!==false||preg_match('/"format":"(IMAGE|VIDEO|DOCUMENT)"/',$raw))throw new \RuntimeException('parameter_templates_not_supported');return ['name'=>$name,'language'=>['code'=>$language]];}
  throw new \RuntimeException('template_not_approved');
 }
}
