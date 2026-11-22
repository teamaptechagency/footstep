<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Database\Schema\Blueprint;
use Carbon\Carbon;
class CheckoutDrafts {
    const TABLE='storefront_checkout_drafts';
    public static function ready(){try{return Cache::remember('checkout_drafts_ready_v1',60,function(){return Schema::hasTable(self::TABLE);});}catch(\Throwable $e){return false;}}
    public static function install(){
        if(!Schema::hasTable(self::TABLE))Schema::create(self::TABLE,function(Blueprint $t){
            $t->bigIncrements('id');$t->char('token_hash',64)->unique();$t->char('owner_hash',64)->index();$t->string('channel',12);$t->string('status',12)->default('open');$t->boolean('has_details')->default(false);$t->longText('details')->nullable();$t->longText('items')->nullable();$t->unsignedBigInteger('combined_order_id')->nullable();$t->dateTime('first_seen');$t->dateTime('last_seen');$t->dateTime('completed_at')->nullable();$t->index(['status','last_seen'],'checkout_draft_status_time');
        });Cache::forget('checkout_drafts_ready_v1');
    }
    public static function owner($request){
        if(!$request->session()->has('checkout_draft_owner'))$request->session()->put('checkout_draft_owner',bin2hex(random_bytes(16)));
        return hash_hmac('sha256',$request->session()->get('checkout_draft_owner'),config('app.key'));
    }
    public static function token($request){
        self::owner($request);
        if(!$request->session()->has('cart_checkout_draft_token'))$request->session()->put('cart_checkout_draft_token',bin2hex(random_bytes(16)));
        return $request->session()->get('cart_checkout_draft_token');
    }
    public static function hash($token){return hash_hmac('sha256','draft:'.$token,config('app.key'));}
    public static function read($record){
        try{return $record->details?json_decode(Crypt::decryptString($record->details),true)?:[]:[];}catch(\Throwable $e){return [];}
    }

    public static function hasPhone($value){
        $value=strtr((string)$value,['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
        if(preg_match('/[^0-9+\s().-]/u',$value))return false;
        $digits=preg_replace('/\D/','',$value);
        return strlen($digits)>=10&&strlen($digits)<=15;
    }
    public static function contactIds(){
        // Legacy drafts may contain only a name/address. Check encrypted details,
        // then cache IDs only; no phone numbers are copied into the cache.
        return Cache::remember('checkout_drafts_phone_ids_v1',30,function(){
            $ids=[];
            DB::table(self::TABLE)->where('has_details',1)->select(['id','details'])->orderBy('id')->chunkById(250,function($rows)use(&$ids){
                foreach($rows as $row){$details=self::read($row);if(self::hasPhone($details['phone']??''))$ids[]=$row->id;}
            });
            return $ids;
        });
    }

    public static function complete($request,$combinedId){
        if(!self::ready()||AnalyticsTracker::staff($request->user()))return;
        $token=$request->input('checkout_draft_token',$request->input('checkout_token'));
        if(!is_string($token)||!preg_match('/^[a-f0-9]{32}$/D',$token))return;
        $hash=self::hash($token);$owner=self::owner($request);$now=Carbon::now('UTC');
        try{
            DB::table(self::TABLE)->insertOrIgnore(['token_hash'=>$hash,'owner_hash'=>$owner,'channel'=>$request->filled('checkout_token')?'direct':'cart','status'=>'completed','has_details'=>false,'combined_order_id'=>$combinedId,'first_seen'=>$now,'last_seen'=>$now,'completed_at'=>$now]);
            DB::table(self::TABLE)->where('token_hash',$hash)->where('owner_hash',$owner)->update(['status'=>'completed','combined_order_id'=>$combinedId,'last_seen'=>$now,'completed_at'=>$now]);
            if($request->input('checkout_draft_token')===$request->session()->get('cart_checkout_draft_token'))$request->session()->forget('cart_checkout_draft_token');
        }catch(\Throwable $e){/* Draft recording never blocks a successful order. */}
    }
}
