<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\{WebsiteLiveChat as C,OnePageCheckout};
class WebsiteChatController extends Controller {
 private function ready(){abort_unless(C::enabled(),503,'Website live chat is temporarily disabled.');abort_unless(C::ready(),503,'Live chat is not ready yet. Please use our contact options.');}
 private function thread($r,$id){$this->ready();$t=DB::table('website_chat_threads')->where('id',$id)->where('owner_hash',C::owner($r))->first();abort_unless($t,404);return $t;}
 public function start(Request $r){$this->ready();$r->merge(['phone'=>OnePageCheckout::phone((string)$r->input('phone'))]);$d=$r->validate(['name'=>'required|string|max:120','phone'=>['required','regex:/^01[3-9][0-9]{8}$/D'],'product_id'=>'nullable|integer','selection'=>'nullable|array|max:20','request_id'=>'required|uuid']);$context=C::product($d['product_id']??null,$d['selection']??[]);$owner=C::owner($r);$key=hash('sha256',$owner.':'.$d['request_id']);
  $id=DB::transaction(function()use($context,$d,$owner,$key){DB::table('website_chat_threads')->insertOrIgnore(['owner_hash'=>$owner,'request_key'=>$key,'contact'=>C::pack(['name'=>$d['name'],'phone'=>$d['phone']]),'product_context'=>$context?C::pack($context):null,'product_id'=>$context['id']??null,'status'=>'open','unread'=>1,'activity_at'=>now(),'created_at'=>now(),'updated_at'=>now()]);return DB::table('website_chat_threads')->where('request_key',$key)->value('id');});return response()->json(['thread_id'=>$id,'context'=>$context,'messages'=>C::messages($id)])->header('Cache-Control','private, no-store');
 }
 public function poll(Request $r,$id){$t=$this->thread($r,$id);$d=$r->validate(['after'=>'nullable|integer|min:0']);return response()->json(['messages'=>C::messages($t->id,(int)($d['after']??0)),'status'=>$t->status])->header('Cache-Control','private, no-store');}
 public function send(Request $r,$id){$t=$this->thread($r,$id);$d=$r->validate(['text'=>'required|string|max:2000','request_id'=>'required|uuid']);$mid=C::message($t,$d['text'],'in',$d['request_id']);return response()->json(['message_id'=>$mid])->header('Cache-Control','private, no-store');}
}
