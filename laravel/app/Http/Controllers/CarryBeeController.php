<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\{CarryBee, StoreOperations};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CarryBeeController extends Controller
{
    private function admin()
    {
        abort_unless(auth()->check() && auth()->user()->user_type==='admin' && !auth()->user()->banned,403);
    }

    public function index($order=null)
    {
        $this->admin();$settings=CarryBee::settings();
        $configured=(bool)($settings['client_id']&&$settings['client_secret']&&$settings['client_context']);
        foreach (['client_id','client_secret','client_context'] as $key) unset($settings[$key]);
        $order=$order?Order::with('orderDetails.product')->findOrFail($order):null;
        $parcel=$order?StoreOperations::shipment($order->id):[];
        return response()->view('backend.platform.carrybee',compact('settings','configured','order','parcel'))->header('Cache-Control','private, no-store');
    }

    public function save(Request $r)
    {
        $this->admin();
        $validator=\Illuminate\Support\Facades\Validator::make($r->only(['mode','store_id','weight','client_id','client_secret','client_context']),['mode'=>'required|in:sandbox,production','store_id'=>'nullable|string|max:100','weight'=>'required|integer|min:1|max:25000','client_id'=>'nullable|string|max:500','client_secret'=>'nullable|string|max:2000','client_context'=>'nullable|string|max:2000']);
        if ($validator->fails()) return back()->withErrors($validator);
        $data=$validator->validated();
        $s=CarryBee::settings();
        // An existing shipment remains bound to its original merchant account.
        $changed=$data['mode']!==$s['mode'];
        foreach (['client_id','client_secret','client_context'] as $key) if (!empty($data[$key]) && $data[$key]!==$s[$key]) $changed=true;
        if ($changed) {
            foreach (\App\Models\BusinessSetting::where('type','like','ops_shipment_%')->pluck('type') as $type) {
                $p=StoreOperations::get(substr($type,4));
                abort_if(($p['provider']??'')==='carrybee' && in_array($p['state']??'',['sending','uncertain','booked'],true),409,'This CarryBee account owns parcels. Keep its identity and environment.');
            }
        }
        foreach (['client_id','client_secret','client_context'] as $key) {
            if (empty($data[$key])) unset($data[$key]);
        }
        $s=array_replace($s,$data,['enabled'=>$r->boolean('enabled')]);
        if (!$s['webhook_secret']) $s['webhook_secret']=bin2hex(random_bytes(32));
        StoreOperations::put('carrybee',$s);StoreOperations::audit('carrybee_settings');
        return back()->with('success','CarryBee settings saved.');
    }

    public function lookup(Request $r)
    {
        $this->admin();$d=$r->validate(['kind'=>'required|in:stores,cities,zones,areas','city'=>'nullable|integer|min:1','zone'=>'nullable|integer|min:1']);
        $path=$d['kind'];
        if (in_array($path,['zones','areas'],true)) {
            abort_unless(!empty($d['city']),422);$path='cities/'.$d['city'].'/zones';
            if ($d['kind']==='areas') {abort_unless(!empty($d['zone']),422);$path.='/'.$d['zone'].'/areas';}
        }
        try {return response()->json(CarryBee::request('GET',$path))->header('Cache-Control','private, no-store');}
        catch (\RuntimeException $e) {return response()->json(['message'=>$e->getMessage()],502);}
    }

    public function action(Request $r,$order)
    {
        $this->admin();
        $d=$r->validate(['action'=>'required|in:book,sync,cancel','item_weight'=>'nullable|integer|min:1|max:25000','delivery_type'=>'nullable|integer|in:1,2','city_id'=>'nullable|integer|min:1','zone_id'=>'nullable|integer|min:1','area_id'=>'nullable|integer|min:1','special_instruction'=>'nullable|string|max:254','reason'=>'required_if:action,cancel|nullable|string|max:199']);
        try {
            if ($d['action']==='book') CarryBee::book($order,$d);
            elseif ($d['action']==='sync') CarryBee::sync($order);
            else {
                $p=StoreOperations::shipment($order);
                abort_unless(($p['provider']??'')==='carrybee' && !empty($p['consignment_id']) && ($p['environment']??'')===CarryBee::settings()['mode'],409);
                CarryBee::request('POST','orders/'.rawurlencode($p['consignment_id']).'/cancel',['cancellation_reason'=>$d['reason']]);
                StoreOperations::audit('carrybee_cancel_requested',$order);
            }
            return back()->with('success','CarryBee request completed. Review parcel details.');
        } catch (\RuntimeException $e) {return back()->withErrors(['carrybee'=>$e->getMessage()]);}
    }

    public function webhook(Request $r)
    {
        $s=CarryBee::settings();$secret=(string)$r->header('X-CB-Webhook-Integration-Header');
        abort_unless($s['enabled'] && $s['webhook_secret'] && hash_equals($s['webhook_secret'],$secret),401);
        // The provider's integration verification request may have no parcel data.
        if (!$r->input('consignment_id') && !$r->input('merchant_order_id')) return response('',202)->header('X-CB-Webhook-Integration-Header',$secret);
        $d=$r->validate(['merchant_order_id'=>['required','regex:/^FS-([0-9]+)$/D'],'consignment_id'=>'required|string|max:100','store_id'=>'required|string|max:100','event'=>'required|string|max:100','timestamptz'=>'required|date']);
        $id=(int)substr($d['merchant_order_id'],3);
        DB::transaction(function()use($id,$d,$s){
            Order::whereKey($id)->lockForUpdate()->firstOrFail();$p=StoreOperations::shipment($id);
            abort_unless(($p['provider']??'')==='carrybee' && ($p['environment']??'')===$s['mode'] && ($p['invoice']??'')===$d['merchant_order_id'] && ($p['store_id']??'')===$d['store_id'],409);
            if (!empty($p['consignment_id'])) abort_unless(hash_equals($p['consignment_id'],$d['consignment_id']),409);
            $at=\Carbon\Carbon::parse($d['timestamptz'])->getTimestamp();
            if ($at<=($p['last_webhook_at']??0)) return;
            $p['last_webhook_at']=$at;$p['consignment_id']=$d['consignment_id'];$p['tracking_code']=$d['consignment_id'];$p['state']='booked';
            $p['status']=$d['event'];$p['synced_at']=now()->toIso8601String();
            StoreOperations::put('shipment_'.$id,$p);
            if (!$p['mock'] && ($mapped=\App\Services\CourierOrderStatus::transition('carrybee',$d['event']))!==null) StoreOperations::applyStatus($id,$mapped,false,$d['event']);
            StoreOperations::audit('carrybee_webhook_'.$d['event'],$id);
        });
        return response('',202)->header('X-CB-Webhook-Integration-Header',$secret);
    }
}
