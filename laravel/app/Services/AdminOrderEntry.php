<?php
namespace App\Services;
use App\Models\{Order,OrderDetail,CombinedOrder,Product,ProductStock};
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\Checkout\ProductQuote;
class AdminOrderEntry {
 public static function create(array $d) {
  return DB::transaction(function()use($d){
   $selections=[];$subtotal=0;$units=0;
   $items=$d['items'];usort($items,function($a,$b){return $a['stock_id']<=>$b['stock_id'];});
   foreach($items as $item){
    $stock=ProductStock::whereKey($item['stock_id'])->lockForUpdate()->first();
    $p=$stock?Product::with('taxes')->find($stock->product_id):null;$qty=(int)$item['quantity'];
    if(!$p||!$p->published||$p->external_link||$p->digital||$qty<max(1,(int)$p->min_qty)||$stock->qty<$qty)throw ValidationException::withMessages(['items'=>'Product / size is unavailable or has insufficient stock.']);
    $q=ProductQuote::selection($p,$stock,$qty);$selections[]=$q;$units+=$qty;$subtotal+=($q['price']+$q['tax'])*$qty;
   }
   $shipping=OnePageCheckout::fee($d['city'],$units);
   $address=OnePageCheckout::shipping(array_intersect_key($d,array_flip(['name','phone','address','city','email'])));
   $combined=new CombinedOrder();$combined->user_id=0;$combined->shipping_address=json_encode($address);$combined->grand_total=round($subtotal+$shipping,2);$combined->save();
   $o=new Order();$o->combined_order_id=$combined->id;$o->user_id=null;$o->seller_id=SingleVendor::ownerId();$o->shipping_address=$combined->shipping_address;$o->shipping_type=$d['city'];$o->payment_type='cash_on_delivery';$o->payment_status='unpaid';$o->delivery_status='pending';$o->code=now()->format('Ymd-His').random_int(1000,9999);$o->date=time();$o->grand_total=$combined->grand_total;$o->coupon_discount=0;$o->delivery_viewed=0;$o->payment_status_viewed=0;$o->save();
   foreach($selections as $i=>$q){$line=new OrderDetail();$line->order_id=$o->id;$line->seller_id=$o->seller_id;$line->product_id=$q['product']->id;$line->variation=$q['stock']->variant;$line->quantity=$q['qty'];$line->price=round($q['price']*$q['qty'],2);$line->tax=round($q['tax']*$q['qty'],2);$line->shipping_cost=$i===0?$shipping:0;$line->shipping_type='home_delivery';$line->payment_status='unpaid';$line->delivery_status='pending';$line->save();$q['stock']->decrement('qty',$q['qty']);$q['product']->increment('num_of_sale',$q['qty']);}
   StoreOperations::put('order_origin_'.$o->id,['source'=>'admin_manual','admin_id'=>auth()->id(),'at'=>now()->toIso8601String()]);StoreOperations::audit('admin_order_created',$o->id);if(!empty($d['entry_token']))StoreOperations::put('admin_entry_'.hash('sha256',$d['entry_token']),['order_id'=>$o->id,'admin_id'=>auth()->id()]);\App\Utility\NotificationUtility::sendNotification($o,'placed');
   return $o;
  });
 }
}
