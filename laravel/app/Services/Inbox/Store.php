<?php
namespace App\Services\Inbox;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
class Store {
 public static function pack($value){return Crypt::encryptString(json_encode($value,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));}
 public static function unpack($value){return $value?json_decode(Crypt::decryptString($value),true):[];}
 public static function business($id){return DB::table('inbox_businesses')->where('id',$id)->first();}
 public static function allowed($user,$id){return $user&&!$user->banned&&($user->user_type==='admin'||($user->user_type==='staff'&&DB::table('inbox_members')->where('user_id',$user->id)->where('business_id',$id)->exists()));}
 public static function authorize($user,$id){abort_unless(self::allowed($user,$id),403);}
 public static function audit($business,$action,$reference=null,$actor=null){DB::table('inbox_audit')->insert(['business_id'=>$business,'action'=>$action,'reference_id'=>$reference,'actor_id'=>$actor,'created_at'=>now(),'updated_at'=>now()]);}
 public static function job($business,$type,$reference,$key){DB::table('inbox_jobs')->insertOrIgnore(['business_id'=>$business,'type'=>$type,'reference_id'=>$reference,'dedupe'=>$key,'state'=>'pending','attempts'=>0,'available_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);}
 public static function message($conversation,$direction,$text,$fields=[]){return DB::table('inbox_messages')->insertGetId(array_merge(['business_id'=>$conversation->business_id,'conversation_id'=>$conversation->id,'direction'=>$direction,'body'=>self::pack(mb_substr($text,0,12000)),'dedupe'=>hash('sha256',random_bytes(32)),'status'=>$direction==='in'?'received':'draft','origin'=>$direction==='in'?'customer':'staff','epoch'=>$conversation->epoch,'created_at'=>now(),'updated_at'=>now()],$fields));}
 public static function takeover($id,$user){return DB::transaction(function()use($id,$user){$c=DB::table('inbox_conversations')->where('id',$id)->lockForUpdate()->first();self::authorize($user,$c->business_id);DB::table('inbox_conversations')->where('id',$id)->update(['mode'=>'manual','takeover_by'=>$user->id,'epoch'=>$c->epoch+1,'updated_at'=>now()]);DB::table('inbox_messages')->where('conversation_id',$id)->where('origin','ai')->whereIn('status',['queued','draft'])->update(['status'=>'superseded']);self::audit($c->business_id,'staff_takeover',$id,$user->id);return DB::table('inbox_conversations')->find($id);});}
 public static function windowOpen($c){return $c->last_inbound_at&&\Carbon\Carbon::parse($c->last_inbound_at)->gt(now()->subHours(24))&&\Carbon\Carbon::parse($c->last_inbound_at)->lte(now()->addMinute());}
 public static function publicBusiness($b){$copy=clone $b;unset($copy->secrets);return $copy;}
 public static function publicChannel($c){$copy=clone $c;unset($copy->credentials);return $copy;}
}
