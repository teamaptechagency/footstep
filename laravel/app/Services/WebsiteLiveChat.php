<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema,Crypt};
use Illuminate\Database\Schema\Blueprint;
use App\Models\Product;
class WebsiteLiveChat {
 public static function enabled(){return false;}
 public static function ready(){return Schema::hasTable('website_chat_threads')&&Schema::hasTable('website_chat_messages');}
 public static function install(){
  if(!Schema::hasTable('website_chat_threads'))Schema::create('website_chat_threads',function(Blueprint $t){$t->engine='InnoDB';$t->bigIncrements('id');$t->string('owner_hash',64)->index();$t->string('request_key',64)->unique();$t->text('contact');$t->longText('product_context')->nullable();$t->unsignedBigInteger('product_id')->nullable()->index();$t->string('status',12)->default('open');$t->unsignedInteger('unread')->default(0);$t->timestamp('activity_at')->nullable()->index();$t->timestamps();});
  if(!Schema::hasTable('website_chat_messages'))Schema::create('website_chat_messages',function(Blueprint $t){$t->engine='InnoDB';$t->bigIncrements('id');$t->unsignedBigInteger('thread_id')->index();$t->string('direction',8);$t->longText('body');$t->string('request_key',64)->unique();$t->unsignedBigInteger('actor_id')->nullable();$t->timestamps();});
 }
 public static function pack($v){return Crypt::encryptString(json_encode($v,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE));}
 public static function unpack($v){return $v?json_decode(Crypt::decryptString($v),true):null;}
 public static function owner($r){if(!$r->session()->has('website_chat_owner'))$r->session()->put('website_chat_owner',bin2hex(random_bytes(24)));return hash_hmac('sha256','website-chat:'.$r->session()->get('website_chat_owner'),config('app.key'));}
 public static function admin($user){return $user&&!$user->banned&&($user->user_type==='admin'||($user->user_type==='staff'&&in_array('8',json_decode($user->staff->role->permissions??'[]',true)?:[],true)));}
 public static function product($id,$selection=[]){
  if(!$id)return null;$p=filter_products(Product::query())->with('stocks')->findOrFail($id);$parts=[];if(!empty($selection['color'])&&is_string($selection['color']))$parts[]=$selection['color'];foreach(json_decode($p->choice_options?:'[]')?:[]as $choice){$v=$selection['attribute_id_'.$choice->attribute_id]??null;if(!is_string($v)||!in_array($v,$choice->values,true)){$parts=[];break;}$parts[]=str_replace(' ','',$v);}$variant=implode('-',$parts);$stock=$p->stocks->firstWhere('variant',$variant);
  $price=$stock?(float)$stock->price:(float)$p->unit_price;if($p->discount_start_date===null||(time()>=$p->discount_start_date&&time()<=$p->discount_end_date)){if($p->discount_type==='percent')$price-=$price*$p->discount/100;elseif($p->discount_type==='amount')$price-=$p->discount;}$tax=0;foreach($p->taxes as $t)$tax+=$t->tax_type==='percent'?$price*$t->tax/100:$t->tax;
  return ['id'=>$p->id,'name'=>$p->name,'url'=>route('product',$p->slug),'image'=>OptimizedImages::asset($p->thumbnail_img),'selected_size'=>$stock?($stock->variant?:'Standard'):null,'available'=>$stock?$stock->qty>=max(1,(int)$p->min_qty):null,'price'=>round($price+$tax,2),'currency'=>'BDT','sizes'=>$p->stocks->take(50)->map(fn($s)=>['size'=>$s->variant?:'Standard','available'=>$s->qty>=max(1,(int)$p->min_qty)])->values()->all()];
 }
 public static function message($thread,$text,$direction,$request,$actor=null){return DB::transaction(function()use($thread,$text,$direction,$request,$actor){$t=DB::table('website_chat_threads')->where('id',$thread->id)->lockForUpdate()->first();abort_unless($t&&$t->status==='open',409,'This conversation is closed. Start a new conversation.');$key=hash('sha256',$t->id.':'.$direction.':'.$request);$existing=DB::table('website_chat_messages')->where('request_key',$key)->first();if($existing)return $existing->id;$id=DB::table('website_chat_messages')->insertGetId(['thread_id'=>$t->id,'direction'=>$direction,'body'=>self::pack($text),'request_key'=>$key,'actor_id'=>$actor,'created_at'=>now(),'updated_at'=>now()]);$updates=['activity_at'=>now(),'updated_at'=>now()];if($direction==='in')$updates['unread']=$t->unread+1;DB::table('website_chat_threads')->where('id',$t->id)->update($updates);return $id;});}
 public static function messages($id,$after=0){return DB::table('website_chat_messages')->where('thread_id',$id)->where('id','>',$after)->orderBy('id')->limit(100)->get()->map(fn($m)=>['id'=>$m->id,'direction'=>$m->direction,'text'=>self::unpack($m->body),'at'=>$m->created_at])->all();}
}
