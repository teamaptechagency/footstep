<?php
namespace App\Services\Inbox;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
class Catalog {
 public static function query($channel){return filter_products(Product::query())->where('digital',0)->where(function($q){$q->whereNull('external_link')->orWhere('external_link','');})->whereIn('id',DB::table('inbox_products')->where('channel_id',$channel->id)->select('product_id'))->with('stocks');}
 public static function facts($product){
  $active=$product->discount_start_date===null||(time()>=$product->discount_start_date&&time()<=$product->discount_end_date);
  $sizes=[];foreach($product->stocks as $s){$price=(float)$s->price;if($active){if($product->discount_type==='percent')$price-=$price*$product->discount/100;elseif($product->discount_type==='amount')$price-=$product->discount;}$tax=0;foreach($product->taxes as $t)$tax+=$t->tax_type==='percent'?$price*$t->tax/100:$t->tax;$sizes[]=['stock_id'=>$s->id,'size'=>$s->variant?:'Standard','available'=>$s->qty>=max(1,(int)$product->min_qty),'stock'=>(int)$s->qty,'price'=>round($price+$tax,2)];}
  return ['id'=>$product->id,'name'=>mb_substr($product->name,0,250),'url'=>route('product',$product->slug),'image'=>uploaded_asset($product->thumbnail_img),'minimum'=>max(1,(int)$product->min_qty),'sizes'=>$sizes];
 }
 public static function candidates($channel,$text){
  $terms=array_values(array_filter(preg_split('/[^\p{L}\p{N}]+/u',mb_strtolower(mb_substr($text,0,2000))),fn($x)=>mb_strlen($x)>2));
  $q=self::query($channel);$found=clone $q;
  if($terms)$found->where(function($p)use($terms){foreach(array_slice($terms,0,12)as $term)$p->orWhere('name','like','%'.str_replace(['%','_'],['\\%','\\_'],$term).'%')->orWhere('slug','like','%'.$term.'%');});
  else $found->whereRaw('1=0');
  return $found->limit(8)->get()->map(fn($p)=>self::facts($p))->values()->all();
 }
 public static function policies($channel){
  $delivery=$channel->store_delivery?('Cash on Delivery. '.json_encode(\App\Services\OnePageCheckout::FEES,JSON_UNESCAPED_UNICODE)):trim((string)$channel->delivery_policy);
  $returns=trim((string)$channel->return_policy);
  if($channel->return_page_id){$page=\App\Models\Page::find($channel->return_page_id);$returns=$page?mb_substr(trim(strip_tags($page->content)),0,4000):'';}
  return ['delivery'=>$delivery?:null,'returns'=>$returns?:null];
 }
 public static function draft($conversation,$productId,$stockId,$quantity,$actor=null){
  $ch=DB::table('inbox_channels')->find($conversation->channel_id);$p=self::query($ch)->findOrFail($productId);$stock=$p->stocks->firstWhere('id',(int)$stockId);abort_unless($stock&&$quantity>=max(1,(int)$p->min_qty)&&$quantity<=99&&$stock->qty>=$quantity,422,'Size or quantity unavailable.');
  $facts=self::facts($p);return DB::table('inbox_order_drafts')->insertGetId(['business_id'=>$conversation->business_id,'conversation_id'=>$conversation->id,'product_id'=>$p->id,'stock_id'=>$stock->id,'quantity'=>$quantity,'details'=>Store::pack($facts),'status'=>'needs_staff_review','actor_id'=>$actor,'created_at'=>now(),'updated_at'=>now()]);
 }
}
