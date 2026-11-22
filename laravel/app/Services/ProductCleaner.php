<?php
namespace App\Services;
use Illuminate\Support\Facades\{DB,Schema,Cache};
class ProductCleaner {
 public static function query(array $f){$q=\App\Models\Product::query();if(!empty($f['q'])){$term=str_replace(['!','%','_'],['!!','!%','!_'],$f['q']);$q->where(function($query)use($term,$f){$query->whereRaw("name LIKE ? ESCAPE '!'",['%'.$term.'%']);if(ctype_digit($f['q']))$query->orWhere('id',(int)$f['q']);});}$field=($f['date_field']??'created_at')==='updated_at'?'updated_at':'created_at';$age=$f['age']??'6';if(in_array($age,['3','6','12'],true))$q->where($field,'<',now()->subMonthsNoOverflow((int)$age));if(!empty($f['from']))$q->where($field,'>=',$f['from'].' 00:00:00');if(!empty($f['to']))$q->where($field,'<=',$f['to'].' 23:59:59');if(($f['published']??'')!=='')$q->where('published',(int)$f['published']);if(($f['ordered']??'')==='never')$q->whereNotExists(function($q){$q->select(DB::raw(1))->from('order_details')->whereColumn('order_details.product_id','products.id');});return $q;}
 public static function clear(){foreach(['products','business_settings','featured_products','best_selling_products','todays_deal_products','todays_deal_products_'.intdiv(time(),900)] as $key)Cache::forget($key);foreach(DB::table('categories')->pluck('id') as $id)Cache::forget('products-category-'.$id);}
 public static function action($id,$action){return DB::transaction(function()use($id,$action){$p=DB::table('products')->where('id',$id)->lockForUpdate()->first();abort_unless($p,404,'Product not found.');if($action==='unpublish'){DB::table('products')->where('id',$id)->update(['published'=>0,'updated_at'=>now()]);return 'Unpublished / বন্ধ করা হয়েছে';}
  abort_if(DB::table('order_details')->where('product_id',$id)->exists(),409,'এই product order history-তে আছে। Delete-এর বদলে Unpublish করুন।');
  if(Schema::hasTable('inbox_order_drafts'))abort_if(DB::table('inbox_order_drafts')->where('product_id',$id)->exists(),409,'Inbox order draft references this product; review it first.');
  foreach(['product_translations','product_stocks','product_taxes','reviews','carts','wishlists','flash_deal_products','inbox_products'] as $table)if(Schema::hasTable($table))DB::table($table)->where('product_id',$id)->delete();
  DB::table('business_settings')->where('type','product_image_versions_v1_'.$id)->delete();
  DB::table('products')->where('id',$id)->delete();return 'Deleted / মুছে ফেলা হয়েছে';});}
}
