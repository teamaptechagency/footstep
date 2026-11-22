<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB,Hash,Http,Schema,Validator};
use Illuminate\Support\Str;
use App\Services\Inbox\{Store,Catalog,Webhooks,Transport,Processor};
class InboxController extends Controller {
 private function admin(){abort_unless(auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);}
 private function business($id){$b=Store::business($id);abort_unless($b,404);Store::authorize(auth()->user(),$id);return $b;}
 private function channel($id){$c=DB::table('inbox_channels')->find($id);abort_unless($c,404);$this->business($c->business_id);return $c;}
 private function conversation($id){$c=DB::table('inbox_conversations')->find($id);abort_unless($c,404);$this->business($c->business_id);return $c;}
 private function validateSafe($r,$rules){$v=Validator::make($r->all(),$rules);if($v->fails())$this->formError($r,$v->errors());return $v->validated();}
 private function formError($r,$errors){
  $businessId=$r->input('business_id') ?: ($r->is('admin/inbox/business') ? $r->input('id') : null); $response=redirect()->route('inbox.settings',$businessId?['business'=>(int)$businessId]:[])->withErrors($errors);
  // Never flash credentials, password, or live-send switches back into a form.
  $response->withInput($r->only(['name','model','graph_version','daily_limit','monthly_limit','input_rate','output_rate','max_output']));
  throw new \Illuminate\Http\Exceptions\HttpResponseException($response);
 }
 private function password($r){if(!Hash::check((string)$r->input('password'),auth()->user()->password))$this->formError($r,['password'=>'Current admin password is missing or incorrect. / বর্তমান admin password সঠিকভাবে দিন।']);}
 public function setup(Request $r){$this->admin();$this->password($r);try{\App\Services\Inbox\ReleaseSchema::install();}catch(\Throwable $e){return back()->withErrors(['setup'=>'Inbox setup could not finish. Check database CREATE/INDEX permissions and existing Inbox schema; existing store data was not changed.']);}return redirect()->route('inbox.index')->with('inbox_notice','Inbox database ready. Create a mock business to start.');}
 public function guide(){ $this->admin();return response()->view('backend.inbox.guide',['guide'=>file_get_contents(resource_path('inbox/AI_INBOX_SETUP_v2.bn.md'))])->header('Cache-Control','private, no-store');}
 public function index(Request $r){
  if($r->input("source")==="website")return app(WebsiteChatAdminController::class)->index($r);
  if(!\App\Services\Inbox\ReleaseSchema::ready())return view('backend.inbox.setup');
  $businesses=DB::table('inbox_businesses')->where('mock',0)->when(auth()->user()->user_type!=='admin',fn($q)=>$q->whereIn('id',DB::table('inbox_members')->where('user_id',auth()->id())->select('business_id')))->get()->map(fn($b)=>Store::publicBusiness($b));
  $b=$r->filled('business')?$this->business((int)$r->business):($businesses->first()?$this->business($businesses->first()->id):null);
  if(!$b||$b->mock)return view('backend.inbox.empty',compact('businesses'));
  $channels=DB::table('inbox_channels')->where('business_id',$b->id)->get()->map(fn($ch)=>Store::publicChannel($ch));
  $q=DB::table('inbox_conversations as c')->join('inbox_channels as ch','ch.id','=','c.channel_id')->where('c.business_id',$b->id)->select('c.*','ch.name as channel_name','ch.type as channel_type');
  if($r->filled('channel'))$q->where('c.channel_id',(int)$r->channel);
  if(in_array($r->type,['messenger','whatsapp'],true))$q->where('ch.type',$r->type);
  if($r->boolean('unread'))$q->where('c.unread','>',0);
  $conversations=$q->orderByDesc('c.activity_at')->paginate(25)->withQueryString();
  $c=$r->filled('conversation')?$this->conversation((int)$r->conversation):null;abort_if($c&&$c->business_id!==$b->id,404);
  $messages=collect();$drafts=collect();$products=collect();
  if($c){$through=DB::table('inbox_messages')->where('conversation_id',$c->id)->max('id');DB::transaction(function()use($c,$through){$locked=DB::table('inbox_conversations')->where('id',$c->id)->lockForUpdate()->first();if(!$locked->last_inbound_id||$locked->last_inbound_id<=$through)DB::table('inbox_conversations')->where('id',$c->id)->update(['unread'=>0]);});
   $messages=DB::table('inbox_messages')->where('conversation_id',$c->id)->orderByDesc('id')->paginate(60,['*'],'history')->withQueryString();foreach($messages as $m){$m->text=Store::unpack($m->body);$m->data=Store::unpack($m->payload);unset($m->body,$m->payload);}
   if(!$r->boolean('poll')){$drafts=DB::table('inbox_order_drafts')->where('conversation_id',$c->id)->orderByDesc('id')->limit(10)->get();foreach($drafts as $d){$d->data=Store::unpack($d->details);unset($d->details);}
   $products=Catalog::query($channels->firstWhere('id',$c->channel_id))->limit(2000)->get();}$c->customer_reference=Store::unpack($c->remote_secret);unset($c->remote_secret);
  }
  $unread=DB::table('inbox_conversations')->where('business_id',$b->id)->sum('unread');
  if($r->boolean('poll'))return response()->json(['list'=>view('backend.inbox.list',compact('conversations','b'))->render(),'messages'=>$c?view('backend.inbox.messages',compact('messages'))->render():'','unread'=>$unread,'incoming'=>DB::table('inbox_messages')->whereIn('conversation_id',DB::table('inbox_conversations')->where('business_id',$b->id)->select('id'))->where('direction','in')->max('id')])->header('Cache-Control','private, no-store');
  $usage=DB::table('inbox_usage')->where('business_id',$b->id)->where('created_at','>=',now()->startOfMonth())->selectRaw('COUNT(*) calls, COALESCE(SUM(input_tokens),0) input_tokens, COALESCE(SUM(output_tokens),0) output_tokens, COALESCE(SUM(cost),0) cost')->first();
  $queueErrors=DB::table('inbox_jobs')->where('business_id',$b->id)->whereIn('state',['failed','pending'])->orderByDesc('id')->limit(8)->get(['id','type','state','error_code','attempts','updated_at']);
  $unread=DB::table('inbox_conversations')->where('business_id',$b->id)->sum('unread');$b=Store::publicBusiness($b);
  return response()->view('backend.inbox.index',compact('businesses','b','channels','conversations','c','messages','drafts','products','usage','queueErrors','unread'))->header('Cache-Control','private, no-store');
 }
 public function settings(Request $r){
  $this->admin();if(!\App\Services\Inbox\ReleaseSchema::ready())return view('backend.inbox.setup');$businesses=DB::table('inbox_businesses')->get()->map(fn($b)=>Store::publicBusiness($b));$b=$r->filled('business')?$this->business((int)$r->business):null;
  $channels=$b?DB::table('inbox_channels')->where('business_id',$b->id)->get()->map(function($ch){$c=Store::publicChannel($ch);$c->product_ids=DB::table('inbox_products')->where('channel_id',$c->id)->pluck('product_id')->implode(',');return $c;}):collect();
  $members=$b?DB::table('inbox_members')->where('business_id',$b->id)->pluck('user_id')->all():[];
  $staff=DB::table('users')->where('user_type','staff')->get(['id','name']);$pages=DB::table('pages')->get(['id','title']);$b=$b?Store::publicBusiness($b):null;
  return response()->view('backend.inbox.settings',compact('businesses','b','channels','staff','members','pages'))->header('Cache-Control','private, no-store');
 }
 public function businessSave(Request $r){
  $this->admin();$data=$this->validateSafe($r,['id'=>'nullable|integer','name'=>'required|string|max:120','model'=>['required','regex:/^gemini-[a-z0-9.\-]{1,90}$/D'],'graph_version'=>['required','regex:/^v[0-9]{2}\.0$/D'],'daily_limit'=>'required|integer|min:1|max:100000','monthly_limit'=>'required|numeric|min:0|max:100000','input_rate'=>'required|numeric|min:0|max:1000','output_rate'=>'required|numeric|min:0|max:1000','max_output'=>'required|integer|min:128|max:2048','gemini_key'=>'nullable|string|max:250','app_secret'=>['nullable','regex:/^[a-fA-F0-9]{32}$/D'],'verify_token'=>['nullable','regex:/^[A-Za-z0-9_\-]{16,128}$/D'],'staff'=>'nullable|array','staff.*'=>'integer|exists:users,id']);
  $b=!empty($data['id'])?$this->business($data['id']):null;
  $changesSecret=$r->filled('gemini_key')||$r->filled('app_secret')||$r->filled('verify_token')||$r->boolean('clear_secrets');
  if($changesSecret||$r->boolean('live_enabled')||($b&&!$b->mock&&$r->boolean('mock')))$this->password($r);
  $secrets=$b?Store::unpack($b->secrets):[];if($r->boolean('clear_secrets'))$secrets=[];
  foreach(['gemini_key','app_secret','verify_token']as $field)if($r->filled($field))$secrets[$field]=$data[$field];
  if($r->boolean('live_enabled')&&($r->boolean('mock')||empty($secrets['app_secret'])||empty($secrets['verify_token'])))$this->formError($r,['live_enabled'=>'Disable Mock mode and save Meta App Secret + verify token before enabling live sending. / Live sending-এর আগে Mock বন্ধ করে Meta App Secret ও verify token দিন।']);
  $values=array_intersect_key($data,array_flip(['name','model','graph_version','daily_limit','monthly_limit','input_rate','output_rate','max_output']));$values+=['mock'=>$r->boolean('mock'),'live_enabled'=>$r->boolean('live_enabled'),'secrets'=>Store::pack($secrets),'updated_at'=>now()];
  $id=DB::transaction(function()use($b,$values,$data){if($b){DB::table('inbox_businesses')->where('id',$b->id)->update($values);$id=$b->id;}else $id=DB::table('inbox_businesses')->insertGetId($values+['uuid'=>(string)Str::uuid(),'created_at'=>now()]);DB::table('inbox_members')->where('business_id',$id)->delete();foreach($data['staff']??[]as $uid){abort_unless(DB::table('users')->where('id',$uid)->where('user_type','staff')->exists(),422);DB::table('inbox_members')->insertOrIgnore(['business_id'=>$id,'user_id'=>$uid]);}Store::audit($id,'business_settings',null,auth()->id());return $id;});
  return redirect()->route('inbox.settings',['business'=>$id])->with('inbox_notice','Business settings saved. Secrets remain server-side.');
 }
 public function channelSave(Request $r){
  $this->admin();$b=$this->business((int)$r->business_id);$data=$this->validateSafe($r,['id'=>'nullable|integer','type'=>'required|in:messenger,whatsapp','name'=>'required|string|max:120','external_id'=>['required','regex:/^[0-9]{1,40}$/D'],'waba_id'=>['nullable','regex:/^[0-9]{1,40}$/D'],'access_token'=>'nullable|string|max:4096','instructions'=>'nullable|string|max:3000','delivery_policy'=>'nullable|string|max:4000','return_policy'=>'nullable|string|max:4000','return_page_id'=>'nullable|integer|exists:pages,id','default_mode'=>'required|in:draft,auto,manual','product_ids'=>['nullable','regex:/^[0-9,\s]*$/D','max:20000']]);
  $ch=!empty($data['id'])?$this->channel($data['id']):null;abort_if($ch&&($ch->business_id!==$b->id||$ch->type!==$data['type']||$ch->external_id!==$data['external_id']),422,'Existing channel identity is immutable; create a new channel.');
  if($r->filled('access_token')||$r->boolean('clear_token'))$this->password($r);
  $creds=$ch?Store::unpack($ch->credentials):[];if($r->boolean('clear_token'))$creds=[];if($r->filled('access_token'))$creds['access_token']=$data['access_token'];
  $exists=DB::table('inbox_channels')->where('type',$data['type'])->where('external_id',$data['external_id'])->when($ch,fn($q)=>$q->where('id','!=',$ch->id))->exists();abort_if($exists,422,'This Page/number is already connected.');
  $values=array_intersect_key($data,array_flip(['type','name','external_id','waba_id','instructions','delivery_policy','return_policy','return_page_id','default_mode']));$values+=['business_id'=>$b->id,'credentials'=>Store::pack($creds),'active'=>$r->boolean('active'),'store_delivery'=>$r->boolean('store_delivery'),'updated_at'=>now()];
  DB::transaction(function()use($ch,$b,$data,$values){$id=$ch?$ch->id:DB::table('inbox_channels')->insertGetId($values+['created_at'=>now()]);if($ch)DB::table('inbox_channels')->where('id',$id)->update($values);
   $ids=array_values(array_unique(array_filter(array_map('intval',preg_split('/[\s,]+/',$data['product_ids']??'')))));abort_if(count($ids)>2000,422,'Maximum 2000 product IDs.');$valid=\App\Models\Product::whereIn('id',$ids)->pluck('id')->all();abort_unless(count($valid)===count($ids),422,'One or more product IDs do not exist.');
   DB::table('inbox_products')->where('channel_id',$id)->delete();foreach($ids as $pid)DB::table('inbox_products')->insert(['channel_id'=>$id,'product_id'=>$pid]);
   DB::table('inbox_conversations')->where('channel_id',$id)->increment('epoch');DB::table('inbox_messages')->whereIn('conversation_id',DB::table('inbox_conversations')->where('channel_id',$id)->select('id'))->whereIn('status',['queued','draft'])->update(['status'=>'superseded']);Store::audit($b->id,'channel_settings',$id,auth()->id());});
  return back()->with('inbox_notice','Channel saved. Product access is explicit; an empty list grants no products.');
 }
 public function testConnection(Request $r,$id){$this->admin();$ch=$this->channel($id);$b=$this->business($ch->business_id);$result=Transport::check($b,$ch);Store::audit($b->id,'connection_check',$ch->id,auth()->id());return back()->with('inbox_notice',$result);}
 public function testGemini(Request $r,$id){$this->admin();$b=$this->business($id);if($b->mock)return back()->with('inbox_notice','Mock AI ready; no Google request made.');$key=Store::unpack($b->secrets)['gemini_key']??'';try{$res=Http::withHeaders(['x-goog-api-key'=>$key])->withOptions(['allow_redirects'=>false])->timeout(15)->get('https://generativelanguage.googleapis.com/v1beta/models/'.$b->model);$ok=$res->successful();}catch(\Throwable $e){$ok=false;}return back()->with('inbox_notice',$ok?'Gemini model/key metadata check passed. No customer content or inference sent.':'Gemini model/key check failed. Check AI Studio model access and key restrictions.');}
 public function simulate(Request $r){$ch=$this->channel((int)$r->channel_id);$b=$this->business($ch->business_id);abort_unless($b->mock,422,'Simulation is only available in mock mode.');$d=$r->validate(['text'=>'required|string|max:4000','customer'=>'required|string|max:60']);$id=Webhooks::receive($ch,'mock-'.hash('sha256',$d['customer']),'mock-'.Str::uuid(),$d['text'],time());return redirect()->route('inbox.index',['business'=>$b->id,'conversation'=>$id]);}
 public function processMock(Request $r,$id){$b=$this->business($id);abort_unless($b->mock,422,'Review processing is mock-only. Use the worker for real channels.');for($i=0;$i<15&&Processor::tick($id);$i++){}return back()->with('inbox_notice','Mock queue processed. No external message or AI request sent.');}
 public function retry(Request $r,$id){$j=DB::table('inbox_jobs')->find($id);abort_unless($j,404);$this->business($j->business_id);abort_unless($j->state==='failed'&&$j->type==='ai',422,'Only AI generation can be manually retried. Unknown delivery outcomes must not be resent.');DB::table('inbox_jobs')->where('id',$id)->update(['state'=>'pending','attempts'=>0,'available_at'=>now(),'updated_at'=>now()]);Store::audit($j->business_id,'retry_ai_job',$id,auth()->id());return back()->with('inbox_notice','AI generation queued again, subject to mode and budget checks.');}
 public function mode(Request $r,$id){$c=$this->conversation($id);$d=$r->validate(['mode'=>'required|in:manual,draft,auto']);if($d['mode']==='manual')Store::takeover($c->id,auth()->user());else DB::transaction(function()use($c,$d){$locked=DB::table('inbox_conversations')->where('id',$c->id)->lockForUpdate()->first();DB::table('inbox_conversations')->where('id',$c->id)->update(['mode'=>$d['mode'],'takeover_by'=>null,'epoch'=>$locked->epoch+1,'updated_at'=>now()]);Store::audit($c->business_id,'mode_'.$d['mode'],$c->id,auth()->id());});return back();}
 public function reply(Request $r,$id){
  $c=$this->conversation($id);$data=$r->validate(['text'=>'required|string|max:1900','request_id'=>'required|uuid','draft_id'=>'nullable|integer']);
  $key=hash('sha256',$c->id.':reply:'.$data['request_id']);
  DB::transaction(function()use($c,$data,$key){$fresh=Store::takeover($c->id,auth()->user());if(DB::table('inbox_messages')->where('dedupe',$key)->exists())return;
   if(!empty($data['draft_id'])){abort_unless(DB::table('inbox_messages')->where('id',$data['draft_id'])->where('conversation_id',$c->id)->where('status','superseded')->where('origin','ai')->exists(),422,'Invalid AI draft.');DB::table('inbox_messages')->where('id',$data['draft_id'])->update(['status'=>'approved']);}
   $mid=Store::message($fresh,'out',$data['text'],['status'=>'queued','origin'=>'staff','actor_id'=>auth()->id(),'dedupe'=>$key]);Store::job($c->business_id,'send',$mid,'send:'.$mid);});if($r->expectsJson())return response()->json(['ok'=>true]);return back()->with('inbox_notice','Reply queued. Staff takeover is active.');
 }
 public function product(Request $r,$id){
  $c=$this->conversation($id);$d=$r->validate(['product_id'=>'required|integer','request_id'=>'required|uuid']);$ch=$this->channel($c->channel_id);$p=Catalog::query($ch)->findOrFail($d['product_id']);$facts=Catalog::facts($p);
  abort_unless(preg_match('#^https://#',$facts['image'])||Store::business($c->business_id)->mock,422,'Product image needs a public HTTPS URL for live delivery.');
  DB::transaction(function()use($c,$d,$facts){$fresh=Store::takeover($c->id,auth()->user());$key=hash('sha256',$c->id.':product:'.$d['request_id']);if(DB::table('inbox_messages')->where('dedupe',$key)->exists())return;$mid=Store::message($fresh,'out',$facts['name']."\n".$facts['url'],['kind'=>'image','payload'=>Store::pack(['image'=>$facts['image'],'product_id'=>$facts['id']]),'status'=>'queued','actor_id'=>auth()->id(),'dedupe'=>$key]);Store::job($c->business_id,'send',$mid,'send:'.$mid);
   // Messenger image attachments do not carry a caption; send the actual product link separately.
   $ch=DB::table('inbox_channels')->find($c->channel_id);if($ch->type==='messenger'){$link=Store::message($fresh,'out',$facts['name']."\n".$facts['url'],['status'=>'queued','actor_id'=>auth()->id()]);Store::job($c->business_id,'send',$link,'send:'.$link);}});return back()->with('inbox_notice','Product photo and link queued.');
 }
 public function orderDraft(Request $r,$id){$c=$this->conversation($id);$d=$r->validate(['stock_id'=>'required|integer','quantity'=>'required|integer|min:1|max:99']);$stock=\App\Models\ProductStock::findOrFail($d['stock_id']);$draft=Catalog::draft($c,$stock->product_id,$stock->id,$d['quantity'],auth()->id());Store::audit($c->business_id,'order_draft_only',$draft,auth()->id());return back()->with('inbox_notice','Order draft saved for staff review. No order created, stock reserved or payment changed.');}
 public function template(Request $r,$id){$c=$this->conversation($id);$ch=$this->channel($c->channel_id);$b=$this->business($c->business_id);abort_unless($ch->type==='whatsapp',422);$d=$r->validate(['name'=>['required','regex:/^[a-z0-9_]{1,100}$/D'],'language'=>['required','regex:/^[a-z]{2}(_[A-Z]{2})?$/D'],'opt_in'=>'accepted','request_id'=>'required|uuid']);try{$template=Transport::template($b,$ch,$d['name'],$d['language']);}catch(\Throwable $e){return back()->withErrors(['template'=>'Template could not be verified as approved and parameter-free.']);}
  DB::transaction(function()use($c,$d,$template){$fresh=Store::takeover($c->id,auth()->user());$key=hash('sha256',$c->id.':template:'.$d['request_id']);if(DB::table('inbox_messages')->where('dedupe',$key)->exists())return;$mid=Store::message($fresh,'out','[Approved template: '.$d['name'].']',['kind'=>'template','status'=>'queued','payload'=>Store::pack(['template'=>$template,'opt_in_attested_by'=>auth()->id()]),'actor_id'=>auth()->id(),'dedupe'=>$key]);Store::job($c->business_id,'send',$mid,'send:'.$mid);Store::audit($c->business_id,'template_optin_attestation',$mid,auth()->id());});return back();
 }
}
