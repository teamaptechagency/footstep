<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\ProductAddons;

class ProductAddonsController extends Controller
{
 public function index(Request $request){$items=DB::table('product_addons')->orderByDesc('id')->get();$products=DB::table('products')->leftJoin('uploads','uploads.id','=','products.thumbnail_img')->select('products.id','products.name','uploads.file_name as image_file')->orderBy('products.name')->get();foreach($products as $product){$image=$product->image_file?\App\Services\ImageVariantManifest::read($product->image_file):[];$path=$image['variants'][160]??$product->image_file;$url=$path?my_asset($path):static_asset('assets/img/placeholder.jpg');$label='#'.$product->id.' · '.$product->name;$product->picker_html='<span class="addon-product-option"><img src="'.e($url).'" alt="" width="36" height="44" loading="lazy" decoding="async"><span>'.e($label).'</span></span>';}$editId=(int)$request->query('edit',0);$edit=$editId?DB::table('product_addons')->where('id',$editId)->first():null;return view('backend.products.addons',compact('items','edit','products'));}
 public function save(Request $request){
  $d=$request->validate(['id'=>'nullable|integer|exists:product_addons,id','name'=>'required|string|max:191','description'=>'nullable|string|max:1000','regular_price'=>'required|numeric|min:0|max:1000000','bundle_price'=>'required|numeric|min:0|max:1000000','discount_type'=>'required|in:none,amount,percent','discount_value'=>'required|numeric|min:0|max:1000000','product_ids'=>'nullable|array|max:2000','product_ids.*'=>'required|integer|min:1|distinct','active'=>'nullable|boolean']);
  if($d['discount_type']==='percent'&&$d['discount_value']>100)throw ValidationException::withMessages(['discount_value'=>'Percentage সর্বোচ্চ 100।']);
  $ids=array_values(array_map('intval',$d['product_ids']??[]));
  if($ids&&DB::table('products')->whereIn('id',$ids)->count()!==count($ids))throw ValidationException::withMessages(['product_ids'=>'সঠিক product ID দিন।']);
  $values=['name'=>$d['name'],'description'=>$d['description']??'','regular_price'=>round($d['regular_price'],2),'bundle_price'=>round($d['bundle_price'],2),'discount_type'=>$d['discount_type'],'discount_value'=>round($d['discount_value'],2),'product_ids'=>json_encode($ids),'active'=>$request->boolean('active'),'updated_at'=>now()];
  if(!empty($d['id']))DB::table('product_addons')->where('id',$d['id'])->update($values);else DB::table('product_addons')->insert($values+['created_at'=>now()]);
  return redirect()->route('product-addons.index')->with('addon_success','Add-on সংরক্ষিত হয়েছে। পুরোনো invoice-এর price অপরিবর্তিত থাকবে।');
 }
 public function disable($id){DB::table('product_addons')->where('id',$id)->update(['active'=>0,'updated_at'=>now()]);return back()->with('addon_success','Add-on বন্ধ হয়েছে; পুরোনো invoice অক্ষত আছে।');}
}
