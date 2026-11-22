<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Services\{StoreOperations as Ops,UnusedImageCleaner as Cleaner};

use App\Models\Order;

class StoreOperationsController extends Controller {

 private function admin(){abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);}

 public function settings(){$this->admin();return redirect()->route('steadfast-accounts.show','legacy');}

 public function save(Request $r){$this->admin();return response()->json(['message'=>'Use Integrations → Steadfast Accounts to edit credentials with admin password.'],409);}

 public function protection(){$this->admin();$ips=Ops::get('blocked_ips');$phones=Ops::get('blocked_phones');$blockedUsers=\App\Models\User::where('user_type','customer')->where('banned',1)->orderBy('id')->paginate(25);return view('backend.platform.customer_protection',compact('ips','phones','blockedUsers'));}

 public function protectionSave(Request $r){$this->admin();$d=$r->validate(['kind'=>'required|in:ip,phone,user','value'=>'required|string|max:100','action'=>'required|in:block,unblock']);$block=$d['action']==='block';if($d['kind']==='user'){if(!ctype_digit($d['value'])||(int)$d['value']<1)return back()->withErrors(['protection'=>'Enter a valid customer user ID.']);$user=\App\Models\User::findOrFail((int)$d['value']);if($user->user_type!=='customer')return back()->withErrors(['protection'=>'Only customer accounts can be managed here.']);$user->banned=$block?1:0;$user->save();}else{$value=$d['kind']==='ip'?$d['value']:\App\Services\OrderProtection::phone($d['value']);if(!($d['kind']==='ip'?filter_var($value,FILTER_VALIDATE_IP):$value))return back()->withErrors(['protection'=>'Enter a valid IP or Bangladesh mobile number.']);if($d['kind']==='ip'&&$block&&$value===$r->ip())return back()->withErrors(['protection'=>'Your current IP cannot be blocked.']);$key=$d['kind']==='ip'?'blocked_ips':'blocked_phones';$list=array_values(array_diff(Ops::get($key),[$value]));if($block)$list[]=$value;Ops::put($key,$list);}Ops::audit('protection_'.$d['kind'].'_'.$d['action']);return back()->with('success','Protection list updated.');}

 public function label($id){$this->admin();$order=Order::with('orderDetails.product')->findOrFail($id);$label=\App\Services\CourierLabel::data($order);abort_unless(($label['state']??'')==='booked'&&!empty($label['consignment_id']),409,'Book/check a confirmed courier parcel before printing.');return response()->view('backend.platform.courier_label',compact('order','label'))->header('Cache-Control','private, no-store')->header('X-Robots-Tag','noindex')->header('Referrer-Policy','no-referrer');}

 public function view($id){$this->admin();$order=Order::with('orderDetails.product.stocks')->findOrFail($id);return view('backend.platform.order_invoice',['order'=>$order,'address'=>json_decode($order->shipping_address,true)?:[],'currency'=>\App\Services\MetaPixel::currency()]);}

 public function edit(Request $r,$id){$this->admin();$d=$r->validate(['name'=>'required|string|max:191','phone'=>'required|regex:/^(?:\+?88)?01[3-9][0-9]{8}$/','address'=>'required|string|max:250','city'=>'required|string|max:100','delivery_charge'=>'required|numeric|min:0|max:10000','remove_ids'=>'nullable|array','remove_ids.*'=>'required|integer|distinct','lines'=>'nullable|array','lines.*.stock_id'=>'nullable|integer','lines.*.quantity'=>'nullable|integer|min:1|max:99']);Ops::edit($id,$d);return back()->with('success','Invoice updated.');}

 public function protect(Request $r,$id){$this->admin();$d=$r->validate(['action'=>'required|in:block_ip,unblock_ip,block_phone,unblock_phone,block_customer,unblock_customer']);$order=Order::findOrFail($id);try{\App\Services\OrderProtection::toggle($order,$d['action'],$r);}catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){if($r->expectsJson())return response()->json(['message'=>$e->getMessage()],$e->getStatusCode());return back()->withErrors(['protection'=>$e->getMessage()]);}if($r->expectsJson())return response()->json(['ok'=>true]);return back()->with('success','IP / number protection updated.');}

 public function cancel($id){$this->admin();Ops::cancel($id);return back()->with('success','Order cancelled locally.');}

 public function send($id){$this->admin();$result=Ops::send($id);return back()->with($result['ok']?'success':'error',$result['ok']?($result['mock']?'Mock shipment created; no courier contacted.':'Order sent to Steadfast.'):$result['message']);}

 public function sync($id){$this->admin();Ops::sync($id);return back()->with('success','Courier status synchronized.');}

 public function block(Request $r){$this->admin();$d=$r->validate(['ip'=>'required|ip','action'=>'required|in:block,unblock']);abort_if($d['action']==='block'&&$d['ip']===$r->ip(),422,'Do not block your current IP.');$ips=Ops::get('blocked_ips');$ips=array_values(array_diff($ips,[$d['ip']]));if($d['action']==='block')$ips[]=$d['ip'];Ops::put('blocked_ips',$ips);return back()->with('success','IP list updated.');}

 public function cleaner(){$this->admin();return view('backend.platform.image_cleaner',['quarantined'=>Cleaner::quarantined()]);}

 public function scan(Request $r){$this->admin();return response()->json(Cleaner::scan(max(0,(int)$r->input('after',0))));}

 public function clean(Request $r){$this->admin();$d=$r->validate(['id'=>'required|integer']);return response()->json(Cleaner::quarantine($d['id']));}

 public function restoreImage(Request $r){$this->admin();$d=$r->validate(['reference'=>'required|string']);return response()->json(Cleaner::restore($d['reference']));}

 public function notifications(Request $r){$this->admin();$r->validate(['category'=>'nullable|in:placed,confirmed,delivery,cancelled,other']);$u=auth()->user();$counts=\App\Services\OrderNotificationFeed::counts($u);$query=\App\Services\OrderNotificationFeed::filter($u->unreadNotifications(),$r->input('category'));return response()->json(['count'=>array_sum($counts),'counts'=>$counts,'items'=>$query->latest()->limit(15)->get()->map(function($n){return \App\Services\OrderNotificationFeed::item($n);})])->header('Cache-Control','private, no-store');}

 public function webhook(Request $r){$settings=Ops::settings();abort_unless($settings['mode']==='live'&&!empty($settings['webhook_token'])&&hash_equals($settings['webhook_token'],(string)$r->bearerToken()),401);$d=$r->validate(['invoice'=>'required|regex:/^FS-[0-9]+$/','consignment_id'=>'required','status'=>'required|string','updated_at'=>'required|date']);$id=(int)substr($d['invoice'],3);$s=Ops::shipment($id);abort_unless($s&&($s['account_id']??'legacy')==='legacy'&&!($s['mock']??false)&&!empty($s['consignment_id'])&&hash_equals((string)$s['consignment_id'],(string)$d['consignment_id']),409); // Webhook is only a signal: pull authoritative status, so duplicate/out-of-order payloads cannot regress local state.

 Ops::sync($id);return response()->json(['ok'=>true]);}

}

