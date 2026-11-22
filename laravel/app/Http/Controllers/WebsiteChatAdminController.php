<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash};
use App\Services\WebsiteLiveChat as C;
class WebsiteChatAdminController extends Controller {
 private function admin(){abort_unless(C::admin(auth()->user()),403);}
 public function toggle(Request $r){abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);$d=$r->validate(['enabled'=>'required|boolean']);$row=\App\Models\BusinessSetting::where('type','website_live_chat_enabled')->first()?:new \App\Models\BusinessSetting();$row->type='website_live_chat_enabled';$row->value=$r->boolean('enabled')?'1':'0';$row->save();\Illuminate\Support\Facades\Cache::forget('business_settings');return back()->with('success',$r->boolean('enabled')?'Website Live Chat enabled.':'Website Live Chat disabled. Existing history is retained.');}
 public function setup(Request $r){abort_unless(auth()->user()->user_type==='admin',403);abort_unless(Hash::check((string)$r->password,auth()->user()->password),422,'Current admin password required.');C::install();return redirect()->route('website-chat.index');}
 public function index(Request $r){$this->admin();if(!C::ready())return view('backend.website_chat.setup');$q=DB::table('website_chat_threads');if(in_array($r->status,['open','closed'],true))$q->where('status',$r->status);if($r->boolean('unread'))$q->where('unread','>',0);$threads=$q->orderByDesc('activity_at')->paginate(25)->withQueryString();foreach($threads as $t){$t->person=C::unpack($t->contact);unset($t->contact,$t->owner_hash,$t->request_key);}
  $thread=$r->filled('thread')?DB::table('website_chat_threads')->find((int)$r->thread):null;$messages=[];$context=null;$person=null;if($thread){$messages=C::messages($thread->id,(int)$r->input('after',0));$context=C::unpack($thread->product_context);$person=C::unpack($thread->contact);DB::table('website_chat_threads')->where('id',$thread->id)->where('updated_at',$thread->updated_at)->update(['unread'=>0]);unset($thread->contact,$thread->owner_hash,$thread->request_key,$thread->product_context);}
  if($r->boolean('poll'))return response()->json(['list'=>view('backend.website_chat.list',compact('threads'))->render(),'incoming'=>DB::table('website_chat_messages')->where('direction','in')->max('id'),'messages'=>$messages,'status'=>$thread->status??null,'unread'=>DB::table('website_chat_threads')->sum('unread')])->header('Cache-Control','private, no-store');
  return response()->view('backend.website_chat.index',compact('threads','thread','messages','context','person'))->header('Cache-Control','private, no-store');
 }
 public function reply(Request $r,$id){$this->admin();$d=$r->validate(['text'=>'required|string|max:2000','request_id'=>'required|uuid']);$thread=DB::table('website_chat_threads')->find($id);abort_unless($thread,404);C::message($thread,$d['text'],'out',$d['request_id'],auth()->id());return $r->expectsJson()?response()->json(['ok'=>true]):back();}
 public function status(Request $r,$id){$this->admin();$d=$r->validate(['status'=>'required|in:open,closed']);DB::table('website_chat_threads')->where('id',$id)->update(['status'=>$d['status'],'updated_at'=>now()]);return back();}
}
