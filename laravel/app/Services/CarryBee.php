<?php
namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\{DB, Http, Validator};

class CarryBee
{
    public static function settings()
    {
        return array_replace(['enabled'=>false, 'mode'=>'sandbox', 'client_id'=>'', 'client_secret'=>'', 'client_context'=>'', 'webhook_secret'=>'', 'store_id'=>'', 'weight'=>500], StoreOperations::get('carrybee'));
    }

    public static function request($method, $path, array $data=[])
    {
        $s=self::settings();
        abort_unless($s['enabled'] && $s['client_id'] && $s['client_secret'] && $s['client_context'], 422, 'Configure CarryBee credentials first.');
        $base=$s['mode']==='production'?'https://developers.carrybee.com':'https://sandbox.carrybee.com';
        $client=Http::acceptJson()->asJson()->withHeaders(['Client-ID'=>$s['client_id'], 'Client-Secret'=>$s['client_secret'], 'Client-Context'=>$s['client_context']])->withOptions(['allow_redirects'=>false, 'connect_timeout'=>5])->timeout(20);
        $response=$method==='GET'?$client->get($base.'/api/v2/'.$path,$data):$client->post($base.'/api/v2/'.$path,$data);
        $json=$response->json();
        if (!$response->successful() || !is_array($json) || ($json['error']??true)!==false) {
            throw new \RuntimeException('CarryBee request failed (HTTP '.$response->status().'). Check credentials and submitted fields in the merchant panel.');
        }
        return $json['data']??[];
    }

    public static function payload(Order $order, array $input)
    {
        $s=self::settings();
        $address=json_decode($order->shipping_address,true)?:[];
        $phone=preg_replace('/\D/','',(string)($address['phone']??''));
        if (strpos($phone,'880')===0) $phone=substr($phone,2);
        $amount=$order->payment_status==='paid'?0:(float)$order->grand_total;
        $amount=ceil($amount);
        $instruction=trim(AdminDeliveryNote::text($order->id));
        $extra=trim((string)($input['special_instruction']??''));
        if ($extra!=='' && $extra!==$instruction) {
            $instruction.=($instruction!==''?"\n":'').$extra;
        }
        if (mb_strlen($instruction)>254) {
            throw \Illuminate\Validation\ValidationException::withMessages(['special_instruction'=>'The delivery note and additional instruction together must be at most 254 characters.']);
        }
        $payload=[
            'store_id'=>$s['store_id'], 'merchant_order_id'=>'FS-'.$order->id,
            'delivery_type'=>$input['delivery_type']??1, 'product_type'=>1,
            'recipient_name'=>$address['name']??'', 'recipient_phone'=>$phone,
            'recipient_address'=>implode(', ',array_filter([$address['address']??'', $address['city']??''])),
            'item_weight'=>$input['item_weight']??$s['weight'],
            'item_quantity'=>(int)$order->orderDetails->sum('quantity'),
            'collectable_amount'=>(int)$amount,
            'product_description'=>mb_substr(CourierLabel::description($order),0,254),
            'special_instruction'=>$instruction,
        ];
        foreach (['city_id','zone_id','area_id'] as $field) if (!empty($input[$field])) $payload[$field]=(int)$input[$field];
        return Validator::make($payload,[
            'store_id'=>'required|string', 'recipient_name'=>'required|string|min:2|max:99',
            'recipient_phone'=>['required','regex:/^01[3-9][0-9]{8}$/D'],
            'recipient_address'=>'required|string|min:10|max:200', 'delivery_type'=>'required|integer|in:1,2',
            'product_type'=>'required|integer|in:1,2,3', 'merchant_order_id'=>'required|string|max:49',
            'item_weight'=>'required|integer|min:1|max:25000', 'item_quantity'=>'required|integer|min:1|max:200',
            'collectable_amount'=>'required|integer|min:0|max:100000', 'product_description'=>'nullable|string|max:254',
            'special_instruction'=>'nullable|string|max:254','city_id'=>'nullable|integer|min:1',
            'zone_id'=>'nullable|integer|min:1','area_id'=>'nullable|integer|min:1',
        ])->validate();
    }

    public static function book($id, array $input)
    {
        $reservation=DB::transaction(function()use($id,$input){
            $order=Order::whereKey($id)->lockForUpdate()->firstOrFail();
            abort_if(in_array($order->delivery_status,['delivered','cancelled'],true),409,'This order cannot be booked.');
            $old=StoreOperations::shipment($id);
            abort_if(in_array($old['state']??'',['sending','uncertain','booked'],true),409,'A courier booking exists or needs reconciliation.');
            $s=self::settings();
            abort_unless($s['enabled']&&$s['client_id']&&$s['client_secret']&&$s['client_context'],422,'Configure CarryBee first.');
            $payload=self::payload($order,$input);
            $attempt=bin2hex(random_bytes(16));
            DB::table('orders')->where('id',$id)->update(['courier_reservation_token'=>$attempt]);
            StoreOperations::put('shipment_'.$id,[
                'provider'=>'carrybee','account_label'=>'CarryBee','state'=>'sending','attempt'=>$attempt,
                'invoice'=>$payload['merchant_order_id'],'store_id'=>$payload['store_id'],
                'recipient_name'=>$payload['recipient_name'],'recipient_phone'=>$payload['recipient_phone'],
                'recipient_address'=>$payload['recipient_address'],'cod_amount'=>$payload['collectable_amount'],
                'item_description'=>$payload['product_description'],'mock'=>$s['mode']!=='production',
                'environment'=>$s['mode'],'status'=>'sending',
            ]);
            return compact('payload','attempt');
        },3);
        try {
            $data=self::request('POST','orders',$reservation['payload']);
            $cid=$data['order']['consignment_id']??null;
            if (!is_string($cid)||$cid==='') throw new \RuntimeException('Missing consignment ID.');
            DB::transaction(function()use($id,$cid,$reservation){
                Order::whereKey($id)->lockForUpdate()->firstOrFail();
                $parcel=StoreOperations::shipment($id);
                abort_unless(($parcel['attempt']??'')===$reservation['attempt'],409);
                $parcel['consignment_id']=$cid;$parcel['tracking_code']=$cid;$parcel['state']='booked';
                if (empty($parcel['last_webhook_at'])) $parcel['status']='created';
                $parcel['synced_at']=now()->toIso8601String();
                StoreOperations::put('shipment_'.$id,$parcel);
                StoreOperations::audit('carrybee_booked',$id);
                if (!$parcel['mock']) DeliveryActivity::record($id,'booked',$parcel);
            });
        } catch (\Throwable $e) {
            DB::transaction(function()use($id){
                Order::whereKey($id)->lockForUpdate()->firstOrFail();$p=StoreOperations::shipment($id);
                if (($p['state']??'')==='sending'){$p['state']='uncertain';StoreOperations::put('shipment_'.$id,$p);}
            });
            throw new \RuntimeException('Booking response uncertain. Sync using FS-'.$id.' before attempting another booking.');
        }
    }

    public static function sync($id)
    {
        $p=StoreOperations::shipment($id);
        abort_unless(($p['provider']??'')==='carrybee',409,'This parcel belongs to another courier.');
        abort_unless(($p['environment']??'')===self::settings()['mode'],409,'Use the original CarryBee environment.');
        $requestedAt=time();
        $d=self::request('GET','orders/'.rawurlencode($p['consignment_id']??$p['invoice']).'/details');
        abort_unless(!empty($d['consignment_id']) && ($d['merchant_order_id']??'')===$p['invoice'] && ($d['store_id']??'')===$p['store_id'],409,'CarryBee parcel identity mismatch.');
        DB::transaction(function()use($id,$d,$requestedAt){
            Order::whereKey($id)->lockForUpdate()->firstOrFail();$p=StoreOperations::shipment($id);
            if (!empty($p['consignment_id'])) abort_unless($p['consignment_id']===$d['consignment_id'],409);
            $providerTime=isset($d['updated_at'])?\Carbon\Carbon::parse($d['updated_at'])->getTimestamp():$requestedAt;
            if ($providerTime<($p['last_webhook_at']??0)) return;
            $p['consignment_id']=$d['consignment_id'];$p['tracking_code']=$d['consignment_id'];$p['state']='booked';
            $p['status']=(string)($d['transfer_status']??'unknown');$p['synced_at']=now()->toIso8601String();
            $p['provider_details']=array_intersect_key($d,array_flip(['transfer_status','collected_amount','cod_fee','delivery_fee','attempt','reason','invoice_id','payment_status','updated_at']));
            StoreOperations::put('shipment_'.$id,$p);
            if (!$p['mock'] && ($mapped=CourierOrderStatus::transition('carrybee',$p['status']))!==null) StoreOperations::applyStatus($id,$mapped,false,$p['status']);
        });
        return StoreOperations::shipment($id);
    }
}
