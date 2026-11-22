<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\{AnalyticsStorage,AnalyticsTracker};
use App\Models\Product;
class StorefrontAnalyticsController extends Controller {
 public function page(Request $r){
  if(!AnalyticsStorage::ready()||AnalyticsTracker::staff($r->user())||AnalyticsTracker::bot($r->userAgent())||$r->header('DNT')==='1'||$r->header('Sec-GPC')==='1')return response('',204)->header('Cache-Control','no-store');
  $d=$r->validate(['path'=>'required|string|max:500']);$path=$d['path'];abort_unless(preg_match('~^/(?:$|shop/?$|size-guide/?$|categories/?$|brands/?$|flash-deals?(?:/[A-Za-z0-9_-]+)?/?$|product/[A-Za-z0-9_-]+/?$|category/[A-Za-z0-9_-]+/?$|brand/[A-Za-z0-9_-]+/?$)~D',$path),422);
  $product=null;$title=$path==='/'?'Home':ucwords(str_replace(['-','/'],' ',trim($path,'/')));if(preg_match('~^/product/([^/]+)/?$~',$path,$m)){$product=Product::where('published',1)->where(function($q)use($m){$q->where('slug',$m[1]);if(ctype_digit($m[1]))$q->orWhere('id',(int)$m[1]);})->first();abort_unless($product,404);$title=$product->getTranslation('name');}
  $data=['path'=>$path,'title'=>mb_substr(strip_tags($title),0,191),'product'=>$product?(int)$product->id:null,'expires'=>time()+21600];$encoded=rtrim(strtr(base64_encode(json_encode($data,JSON_UNESCAPED_UNICODE)),'+/','-_'),'=');
  return response()->json(['payload'=>$encoded.'.'.hash_hmac('sha256',$encoded,config('app.key')),'csrf'=>csrf_token()])->header('Cache-Control','private, no-store');
 }
}
