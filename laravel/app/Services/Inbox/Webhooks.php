<?php
namespace App\Services\Inbox;
use Illuminate\Support\Facades\DB;
class Webhooks {
 public static function signature($raw,$header,$secret){return is_string($secret)&&strlen($secret)>=16&&is_string($header)&&hash_equals('sha256='.hash_hmac('sha256',$raw,$secret),$header);}
 public static function ingest($business,$body){
  if(!in_array($body['object']??'', ['page','whatsapp_business_account'],true))return;
  foreach(array_slice($body['entry']??[],0,100)as $entry){
   if($body['object']==='page'){
    $ch=DB::table('inbox_channels')->where('business_id',$business->id)->where('type','messenger')->where('external_id',(string)($entry['id']??''))->where('active',1)->first();if(!$ch)continue;
    foreach($entry['messaging']??[]as $event){
     if(isset($event['delivery']))foreach($event['delivery']['mids']??[]as $mid)self::status($ch,$mid,'delivered');
     if(isset($event['read']['watermark'])){DB::table('inbox_messages')->where('business_id',$ch->business_id)->whereIn('conversation_id',DB::table('inbox_conversations')->where('channel_id',$ch->id)->where('remote_hash',hash('sha256',(string)($event['sender']['id']??'')))->select('id'))->where('direction','out')->whereNotNull('sent_at')->where('sent_at','<=',\Carbon\Carbon::createFromTimestamp((int)($event['read']['watermark']/1000))->timezone(config('app.timezone'))->format('Y-m-d H:i:s'))->whereIn('status',['sent','delivered'])->update(['status'=>'read','read_at'=>now()]);}
     if(!isset($event['message']['mid']))continue;
     $m=$event['message'];if(!empty($m['is_echo'])){self::echoMessage($ch,$event);continue;}
     $remote=(string)($event['sender']['id']??'');if(!$remote||($event['recipient']['id']??'')!==$ch->external_id)continue;
     self::receive($ch,$remote,(string)$m['mid'],$m['text']??'[Attachment — staff review required]',(int)(($event['timestamp']??0)/1000),isset($m['text'])?'text':'attachment');
    }
   }else foreach($entry['changes']??[]as $change){
    if(($change['field']??'')!=='messages')continue;$v=$change['value']??[];
    $ch=DB::table('inbox_channels')->where('business_id',$business->id)->where('type','whatsapp')->where('external_id',(string)($v['metadata']['phone_number_id']??''))->where('active',1)->first();if(!$ch||($ch->waba_id&&(string)$entry['id']!==$ch->waba_id))continue;
    foreach($v['statuses']??[]as $s)self::status($ch,$s['id']??'',$s['status']??'',(int)($s['errors'][0]['code']??0));
    foreach($v['messages']??[]as $m){$text=$m['text']['body']??$m['button']['text']??$m['interactive']['button_reply']['title']??$m['interactive']['list_reply']['title']??'[Attachment — staff review required]';self::receive($ch,(string)($m['from']??''),(string)($m['id']??''),$text,(int)($m['timestamp']??0),isset($m['text'])?'text':'attachment');}
   }
  }
 }
 public static function receive($ch,$remote,$mid,$text,$timestamp,$kind='text'){
  if(!$remote||!$mid||strlen($mid)>191||strlen($remote)>100)return null;
  // Future timestamps must never extend the provider messaging window.
  $timestamp=min($timestamp,time());if($timestamp<1)$timestamp=1;
  return DB::transaction(function()use($ch,$remote,$mid,$text,$timestamp,$kind){
   $hash=hash('sha256',$remote);DB::table('inbox_conversations')->insertOrIgnore(['business_id'=>$ch->business_id,'channel_id'=>$ch->id,'remote_hash'=>$hash,'remote_secret'=>Store::pack($remote),'label'=>'Customer · '.substr($hash,0,8),'mode'=>$ch->default_mode,'unread'=>0,'epoch'=>0,'created_at'=>now(),'updated_at'=>now()]);
   $c=DB::table('inbox_conversations')->where('channel_id',$ch->id)->where('remote_hash',$hash)->lockForUpdate()->first();$dedupe=hash('sha256',$ch->id.':in:'.$mid);
   if(DB::table('inbox_messages')->where('dedupe',$dedupe)->exists())return $c->id;
   $id=Store::message($c,'in',$text,['external_id'=>$mid,'dedupe'=>$dedupe,'kind'=>$kind]);
   $latest=!$c->last_inbound_at||$timestamp>=\Carbon\Carbon::parse($c->last_inbound_at)->timestamp;
   $updates=['unread'=>$c->unread+1,'activity_at'=>now(),'updated_at'=>now()];
   if($latest)$updates+=['last_inbound_id'=>$id,'last_inbound_at'=>\Carbon\Carbon::createFromTimestamp($timestamp)->timezone(config('app.timezone'))->format('Y-m-d H:i:s')];
   DB::table('inbox_conversations')->where('id',$c->id)->update($updates);
   if($latest&&$c->mode!=='manual'&&!$c->takeover_by)Store::job($ch->business_id,'ai',$id,'ai:'.$id);
   return $c->id;
  });
 }
 public static function status($ch,$mid,$status,$error=0){
  if(!$mid||strlen($mid)>191||!in_array($status,['sent','delivered','read','failed'],true))return;
  $key=hash('sha256',$ch->id.':'.$mid.':'.$status.':'.$error);
  DB::table('inbox_receipts')->insertOrIgnore(['channel_id'=>$ch->id,'external_id'=>$mid,'status'=>$status,'error'=>$error,'dedupe'=>$key,'created_at'=>now(),'updated_at'=>now()]);
  $receipt=DB::table('inbox_receipts')->where('dedupe',$key)->first();
  Store::job($ch->business_id,'receipt',$receipt->id,'receipt:'.$receipt->id);
  self::applyStatus($ch,$mid,$status,$error);
 }
 public static function receipt($id){
  $r=DB::table('inbox_receipts')->find($id);if(!$r)return;
  $ch=DB::table('inbox_channels')->find($r->channel_id);if(!$ch)return;
  if(!DB::table('inbox_messages')->whereIn('conversation_id',DB::table('inbox_conversations')->where('channel_id',$ch->id)->select('id'))->where('external_id',$r->external_id)->exists())throw new \RuntimeException('receipt_waiting_for_send');
  self::applyStatus($ch,$r->external_id,$r->status,$r->error);
 }
 private static function applyStatus($ch,$mid,$status,$error=0){
  $rank=['queued'=>0,'sending'=>1,'uncertain'=>1,'failed'=>1,'sent'=>2,'delivered'=>3,'read'=>4];if(!in_array($status,['sent','delivered','read','failed'],true))return;
  DB::transaction(function()use($ch,$mid,$status,$error,$rank){$m=DB::table('inbox_messages')->whereIn('conversation_id',DB::table('inbox_conversations')->where('channel_id',$ch->id)->select('id'))->where('external_id',$mid)->where('direction','out')->lockForUpdate()->first();if(!$m||($rank[$status]??0)<($rank[$m->status]??0))return;$v=['status'=>$status,'updated_at'=>now()];if($status==='failed')$v['error_code']='meta_delivery_'.(int)$error;else $v[$status.'_at']=now();DB::table('inbox_messages')->where('id',$m->id)->update($v);});
 }
 private static function echoMessage($ch,$event){
  $mid=$event['message']['mid'];if(DB::table('inbox_messages')->where('business_id',$ch->business_id)->where('external_id',$mid)->exists())return;
  $remote=(string)($event['recipient']['id']??'');$c=DB::table('inbox_conversations')->where('channel_id',$ch->id)->where('remote_hash',hash('sha256',$remote))->first();if(!$c)return;
  DB::transaction(function()use($c,$mid,$event){$c=DB::table('inbox_conversations')->where('id',$c->id)->lockForUpdate()->first();if(DB::table('inbox_messages')->where('business_id',$c->business_id)->where('external_id',$mid)->exists())return;$dedupe=hash('sha256',$c->channel_id.':echo:'.$mid);if(DB::table('inbox_messages')->where('dedupe',$dedupe)->exists())return;Store::message($c,'out',$event['message']['text']??'[External reply]', ['external_id'=>$mid,'dedupe'=>$dedupe,'status'=>'sent','origin'=>'external','sent_at'=>now()]);DB::table('inbox_conversations')->where('id',$c->id)->update(['mode'=>'manual','epoch'=>$c->epoch+1]);});
 }
}
