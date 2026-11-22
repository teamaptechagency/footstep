<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\ProductCleaner;
class ProductCleanerController extends Controller {
 private function admin(){abort_unless(auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);}
 public function index(Request $r){$this->admin();$filters=$r->validate(['q'=>'nullable|string|max:100','age'=>'nullable|in:all,3,6,12','date_field'=>'nullable|in:created_at,updated_at','from'=>'nullable|date_format:Y-m-d','to'=>array_filter(['nullable','date_format:Y-m-d',$r->filled('from')?'after_or_equal:from':null]),'published'=>'nullable|in:0,1','ordered'=>'nullable|in:never']);$products=ProductCleaner::query($filters)->withCount('orderDetails')->withSum('stocks','qty')->orderBy('created_at')->orderBy('id')->paginate(30)->withQueryString();return view('backend.platform.product_cleaner',compact('products','filters'));}
 public function action(Request $r){$this->admin();$d=$r->validate(['ids'=>'required|array|min:1|max:10','ids.*'=>'required|integer|min:1|distinct','action'=>'required|in:delete,unpublish']);$results=[];foreach($d['ids'] as $id){try{$results[]=['id'=>$id,'ok'=>true,'message'=>ProductCleaner::action($id,$d['action'])];}catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){$results[]=['id'=>$id,'ok'=>false,'message'=>$e->getMessage()?:'Product retained.'];}catch(\Throwable $e){$results[]=['id'=>$id,'ok'=>false,'message'=>'Product retained; database/dependency check failed.'];}}ProductCleaner::clear();return response()->json(['results'=>$results])->header('Cache-Control','private, no-store');}
}
