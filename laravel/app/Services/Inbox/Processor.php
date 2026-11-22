<?php
namespace App\Services\Inbox;
use Illuminate\Support\Facades\DB;
class Processor {
 public static function tick($business=null){return false;}
 private static function retiredTick($business=null){
  // A single short DB claim supports multiple workers; expired leases are recovered safely.
  $job=DB::transaction(function()use($business){
   $q=DB::table('inbox_jobs')->where(function($q){$q->where(function($q){$q->where('state','pending')->where('available_at','<=',now());})->orWhere(function($q){$q->where('state','running')->where('reserved_at','<',now()->subMinutes(3));});});
   if($business)$q->where('business_id',$business);
   $j=$q->orderBy('id')->lockForUpdate()->first();if(!$j)return null;
   DB::table('inbox_jobs')->where('id',$j->id)->update(['state'=>'running','reserved_at'=>now(),'attempts'=>$j->attempts+1,'updated_at'=>now()]);return $j;
  });
  if(!$job)return false;
  DB::table('inbox_businesses')->where('id',$job->business_id)->update(['last_worker_at'=>now()]);
  try{
   if($job->type==='ai')self::ai($job->reference_id);elseif($job->type==='send')self::send($job->reference_id);elseif($job->type==='receipt')Webhooks::receipt($job->reference_id);
   DB::table('inbox_jobs')->where('id',$job->id)->update(['state'=>'done','error_code'=>null,'updated_at'=>now()]);
  }catch(\Throwable $e){
   // Never persist API exception bodies/URLs, tokens, customer text or stack traces.
   $code=preg_match('/^[a-z0-9_]{1,90}$/D',$e->getMessage())?$e->getMessage():'inbox_processing_error';
   $retry=(($code==='retry_meta_429'||in_array($code,['gemini_http_429','gemini_http_503'],true))&&$job->attempts<3)||($code==='receipt_waiting_for_send'&&$job->attempts<6);
   DB::table('inbox_jobs')->where('id',$job->id)->update(['state'=>$retry?'pending':'failed','available_at'=>now()->addSeconds(30*(2**min(5,$job->attempts))),'error_code'=>$code,'updated_at'=>now()]);
   if($job->type==='send')DB::table('inbox_messages')->where('id',$job->reference_id)->whereNotIn('status',['sent','delivered','read','mock_sent'])->update(['status'=>$retry?'queued':(strpos($code,'send_uncertain')===0?'uncertain':'failed'),'error_code'=>$code,'updated_at'=>now()]);
   Store::audit($job->business_id,$code,$job->reference_id);
  }
  return true;
 }
 private static function ai($id){
  $m=DB::table('inbox_messages')->find($id);if(!$m)return;$c=DB::table('inbox_conversations')->find($m->conversation_id);$ch=DB::table('inbox_channels')->find($c->channel_id);$b=Store::business($c->business_id);
  if(!$ch->active||$c->mode==='manual'||$c->takeover_by||$c->last_inbound_id!==$m->id||!Store::windowOpen($c))return;
  if(DB::table('inbox_messages')->where('source_id',$id)->where('origin','ai')->exists())return;
  $text=Store::unpack($m->body);
  $history=DB::table('inbox_messages')->where('conversation_id',$c->id)->where('direction','in')->orderByDesc('id')->limit(6)->get()->reverse()->map(function($v){$text=Store::unpack($v->body);return mb_substr(preg_replace(['/\b\+?[0-9][0-9\s()-]{8,}\b/','/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/i'],['[phone]','[email]'],$text),0,1800);})->values()->all();
  $facts=Catalog::candidates($ch,$text.' '.implode(' ',array_slice($history,-3)));
  $plan=$m->kind==='attachment'?['intent'=>'handoff','language'=>'bn','product_ids'=>[]]:Gemini::classify($b,$ch,$m,$facts,$history);
  // Re-fetch current stock/price after inference. Model is only an intent/ID selector.
  $facts=Catalog::query($ch)->whereIn('id',$plan['product_ids'])->get()->map(fn($p)=>Catalog::facts($p))->all();$text=Gemini::text($plan,$ch,$facts);
  DB::transaction(function()use($c,$m,$ch,$plan,$text,$facts){
   $fresh=DB::table('inbox_conversations')->where('id',$c->id)->lockForUpdate()->first();if($fresh->epoch!==$c->epoch||$fresh->mode==='manual'||$fresh->takeover_by||$fresh->last_inbound_id!==$m->id)return;
   if(DB::table('inbox_messages')->where('source_id',$m->id)->where('origin','ai')->exists())return;
   $auto=$fresh->mode==='auto'&&$plan['intent']!=='handoff';
   $id=Store::message($fresh,'out',$text,['origin'=>'ai','source_id'=>$m->id,'status'=>$auto?'queued':'draft','payload'=>Store::pack(['plan'=>$plan,'product_ids'=>array_column($facts,'id')])]);
   if($auto)Store::job($c->business_id,'send',$id,'send:'.$id);
  });
 }
 private static function send($id){
  // Keep this conversation lock through send: staff takeover and provider calls cannot race.
  DB::transaction(function()use($id){
   $m=DB::table('inbox_messages')->find($id);if(!$m)return;
   $c=DB::table('inbox_conversations')->where('id',$m->conversation_id)->lockForUpdate()->first();
   $m=DB::table('inbox_messages')->where('id',$id)->lockForUpdate()->first();if(in_array($m->status,['sent','delivered','read','mock_sent','superseded'],true))return;
   if(in_array($m->status,['sending','uncertain'],true)){DB::table('inbox_messages')->where('id',$id)->update(['status'=>'uncertain','error_code'=>'send_uncertain_recovered']);return;}
   $ch=DB::table('inbox_channels')->find($c->channel_id);$b=Store::business($c->business_id);
   if(!$ch->active)throw new \RuntimeException('channel_disabled');
   if($m->origin==='ai'&&($c->mode!=='auto'||$c->takeover_by||$c->epoch!==$m->epoch||$c->last_inbound_id!==$m->source_id)){DB::table('inbox_messages')->where('id',$id)->update(['status'=>'superseded']);return;}
   if($m->kind!=='template'&&!Store::windowOpen($c))throw new \RuntimeException('messaging_window_closed');
   if($m->kind==='template'&&($ch->type!=='whatsapp'||!$m->actor_id))throw new \RuntimeException('template_requires_staff');
   // Persist the send-start marker outside this transaction's rollback semantics by
   // committing via afterCommit is unsafe; instead a crash is detected by the job lease
   // and attempt count before re-entering a live provider operation (see worker guard).
   $job=DB::table('inbox_jobs')->where('dedupe','send:'.$id)->first();
   if(!$b->mock&&$job&&$job->attempts>1&&!in_array($job->error_code,['retry_meta_429'],true))throw new \RuntimeException('send_uncertain_recovered');
   DB::table('inbox_messages')->where('id',$id)->update(['status'=>'sending']);
   $result=Transport::send($b,$ch,$c,$m);
   DB::table('inbox_messages')->where('id',$id)->update(['status'=>$b->mock?'mock_sent':'sent','external_id'=>$result['message_id'],'sent_at'=>now(),'error_code'=>null,'updated_at'=>now()]);
  });
 }
}
