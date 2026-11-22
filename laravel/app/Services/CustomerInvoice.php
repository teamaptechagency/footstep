<?php
namespace App\Services;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerInvoice
{
 public static function editable($order){
  if(!in_array($order->delivery_status,['pending','confirmed'],true)||$order->payment_status==='paid'||(float)$order->coupon_discount>0)return false;
  $shipment=StoreOperations::shipment($order->id);
  return !$shipment||!empty($shipment['mock']);
 }
 public static function update($id,array $data){
  return DB::transaction(function()use($id,$data){
   $order=Order::whereKey($id)->lockForUpdate()->firstOrFail();
   abort_unless(self::editable($order),409,'এই অর্ডার আর customer edit করা যাবে না। সহায়তার জন্য যোগাযোগ করুন।');
   $details=$order->orderDetails()->get();
   if(array_diff(array_map('intval',array_keys($data['lines'])), $details->pluck('id')->all())||count($data['lines'])!==$details->count())throw ValidationException::withMessages(['lines'=>'শুধু এই invoice-এর সব product-এর size/quantity দিন।']);
   foreach($details as $detail){$line=$data['lines'][$detail->id];if(isset($line['addons_present']))ProductAddons::syncSelection($detail,$line['addon_ids']??[]);}
   $address=json_decode($order->shipping_address,true)?:[];
   $input=['lines'=>$data['lines'],'remove_ids'=>[],'delivery_charge'=>$details->sum('shipping_cost'),'_customer_edit'=>true];
   foreach(['name','phone','address','city'] as $key)$input[$key]=$address[$key]??'';
   $updated=StoreOperations::edit($id,$input);
   // Audit contains IDs/time only; no customer contact/address in logs.
   DB::table('business_settings')->updateOrInsert(['type'=>'customer_invoice_edit_last_'.$id],['value'=>json_encode(['at'=>now()->toIso8601String()])]);
   return $updated;
  });
 }
}
