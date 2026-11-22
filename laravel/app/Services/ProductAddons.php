<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductAddons
{
 public static function ids($ids){
  if(is_string($ids))$ids=json_decode($ids,true);
  if(!is_array($ids)||count($ids)>10||array_filter($ids,function($v){return !is_numeric($v)||(int)$v<1||(string)(int)$v!==(string)$v;}))throw ValidationException::withMessages(['addon_ids'=>'সঠিক add-on বাছুন (সর্বোচ্চ ১০টি)।']);
  $ids=array_values(array_unique(array_map('intval',$ids)));sort($ids);return $ids;
 }
 public static function price($addon){
  $price=(float)$addon->bundle_price;$discount=(float)$addon->discount_value;
  if($addon->discount_type==='percent')$price-=$price*$discount/100;
  elseif($addon->discount_type==='amount')$price-=$discount;
  return round(max(0,$price),2);
 }
 public static function available($productId){
  return DB::table('product_addons')->where('active',1)->orderBy('id')->get()->filter(function($a)use($productId){$ids=json_decode($a->product_ids?:'[]',true);return is_array($ids)&&in_array((int)$productId,array_map('intval',$ids),true);})->map(function($a){return ['id'=>(int)$a->id,'name'=>$a->name,'description'=>$a->description,'regular_price'=>(float)$a->regular_price,'price'=>self::price($a)];})->values()->all();
 }
 public static function quote($productId,$ids,$quantity=1,$strict=true,array $freeIds=[]){
  $ids=self::ids($ids?:[]);$available=collect(self::available($productId))->keyBy('id');$items=[];
  foreach($ids as $id){$a=$available->get($id);if(!$a){if($strict)throw ValidationException::withMessages(['addon_ids'=>'এই add-on এখন আর পাওয়া যাচ্ছে না। আবার বাছুন।']);continue;}$a['exit_free']=in_array((int)$id,$freeIds,true);if($a['exit_free'])$a['price']=0.0;$a['quantity']=(int)$quantity;$a['total']=round($a['price']*$quantity,2);$items[]=$a;}
  return ['items'=>$items,'total'=>round(array_sum(array_column($items,'total')),2)];
 }
 public static function attach($detail,$ids,array $freeIds=[]){
  $q=self::quote($detail->product_id,$ids,$detail->quantity,true,$freeIds);
  foreach($q['items'] as $a)DB::table('order_addons')->insert(['order_id'=>$detail->order_id,'order_detail_id'=>$detail->id,'addon_id'=>$a['id'],'name'=>$a['name'].(!empty($a['exit_free'])?' (Free offer)':''),'regular_price'=>$a['regular_price'],'unit_price'=>$a['price'],'quantity'=>$a['quantity'],'total'=>$a['total'],'created_at'=>now(),'updated_at'=>now()]);
  return $q['total'];
 }
 public static function lines($orderId){return DB::table('order_addons')->where('order_id',$orderId)->orderBy('id')->get();}
 // Caller holds the order lock inside the customer invoice transaction.
 public static function syncSelection($detail,$ids){
  $ids=self::ids($ids?:[]);
  $existing=DB::table('order_addons')->where('order_id',$detail->order_id)->where('order_detail_id',$detail->id)->get();
  $new=array_values(array_diff($ids,$existing->pluck('addon_id')->map(function($id){return (int)$id;})->all()));
  self::quote($detail->product_id,$new,$detail->quantity); // Validate all new selections before modifying snapshots.
  $remove=$existing->filter(function($row)use($ids){return !in_array((int)$row->addon_id,$ids,true);})->pluck('id')->all();
  if($remove)DB::table('order_addons')->whereIn('id',$remove)->delete();
  if($new)self::attach($detail,$new);
 }
 public static function total($orderId){return (float)DB::table('order_addons')->where('order_id',$orderId)->sum('total');}
 public static function reconcile($orderId){
  foreach(self::lines($orderId) as $line){$detail=DB::table('order_details')->where('order_id',$orderId)->where('id',$line->order_detail_id)->first();if(!$detail)DB::table('order_addons')->where('id',$line->id)->delete();else DB::table('order_addons')->where('id',$line->id)->update(['quantity'=>$detail->quantity,'total'=>round($line->unit_price*$detail->quantity,2),'updated_at'=>now()]);}
  return self::total($orderId);
 }
}
