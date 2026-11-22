<?php
namespace App\Services;
use App\Models\{Upload,Product,BusinessSetting};
use Illuminate\Support\Facades\{Cache,DB};
class ImageOptimizationPanel {
 const KEY='image_optimization_auto_v1';
 public static function automatic(){return Cache::remember(self::KEY,60,function(){return BusinessSetting::where('type',self::KEY)->value('value')!=='0';});}
 private static function logPath($id){return storage_path('app/image-optimization/'.(int)$id.'.json');}
 public static function record($upload,$mode,$result){try{return Cache::lock('image-source-'.hash('sha256',$upload->file_name),300)->block(5,function()use($upload,$mode,$result){if(!is_file(public_path($upload->file_name))||!Upload::where('id',$upload->id)->exists())return;return self::recordUnlocked($upload,$mode,$result);});}catch(\Throwable $ignored){return;}}
 private static function recordUnlocked($upload,$mode,$result){
  try{$info=OptimizedImages::inspect($upload);if(!$info)return;$path=self::logPath($upload->id);if(!is_dir(dirname($path)))mkdir(dirname($path),0700,true);$prior=is_file($path)?json_decode(file_get_contents($path),true):[];
   $entry=['source_key'=>$info['source_key'],'mode'=>$mode,'at'=>now()->toIso8601String(),'result'=>$result?'optimized':'failed'];$prior=is_array($prior)?$prior:[];$prior[]=$entry;$temp=$path.'.'.bin2hex(random_bytes(4));file_put_contents($temp,json_encode(array_slice($prior,-12)),LOCK_EX);rename($temp,$path);
  }catch(\Throwable $e){/* Reporting must not interrupt a product save. */}
 }
 public static function detail($upload){
  $info=OptimizedImages::inspect($upload);$history=[];$path=self::logPath($upload->id);if(is_file($path)){try{$history=json_decode(file_get_contents($path),true)?:[];}catch(\Throwable $e){}}
  if($info)$history=array_values(array_filter($history,fn($h)=>($h['source_key']??null)===$info['source_key']));else $history=[];
  $last=$history?end($history):null;$variants=$info['variants']??[];$display=null;foreach($variants as $v)if(!$display||abs($v['width']-600)<abs($display['width']-600))$display=$v;
  $before=$info['source_bytes']??null;$after=$display['bytes']??null;
  return ['id'=>$upload->id,'name'=>$upload->file_original_name.'.'.$upload->extension,'file'=>$upload->file_name,'url'=>my_asset($upload->file_name),'width'=>$info['width']??null,'height'=>$info['height']??null,'original_bytes'=>$before,'display_bytes'=>$after,'display_width'=>$display['width']??null,'saved_percent'=>$before&&$after!==null?round((1-$after/$before)*100,1):null,'variants'=>$variants,'variant_total_bytes'=>array_sum(array_column($variants,'bytes')),'status'=>!$info?'unavailable':(count($variants)===($info['expected']??0)?'optimized':($variants?'partial':'pending')),'mode'=>$last['mode']??($variants?'existing / untracked':'not processed'),'last_at'=>$last['at']??null,'history'=>array_reverse($history)];
 }
 public static function thumbnail($p){
  $u=Upload::find($p->thumbnail_img);$size=null;$path=$u?realpath(public_path($u->file_name)):false;$root=realpath(public_path('uploads'));
  if($path&&$root&&strpos(str_replace('\\','/',$path),rtrim(str_replace('\\','/',$root),'/').'/')===0)$size=@getimagesize($path);
  return ['product_id'=>$p->id,'product'=>$p->name,'upload_id'=>$u->id??null,'url'=>$u?my_asset($u->file_name):null,'width'=>$size[0]??null,'height'=>$size[1]??null,'status'=>!$size?'missing_or_unsupported':($size[0]===450&&$size[1]===600?'valid':'wrong_size')];
 }
 public static function convert($productId,$expected){
  $p=Product::findOrFail($productId);abort_unless((string)$p->thumbnail_img===(string)$expected,409,'Thumbnail changed. Run checker again.');$u=Upload::findOrFail($expected);
  $request=\Illuminate\Http\Request::create('/','POST',['thumbnail_img'=>(string)$u->id,'photos'=>'']);ProductPhotos::prepare($request);$new=(string)$request->input('thumbnail_img');
  $changed=DB::table('products')->where('id',$p->id)->where('thumbnail_img',$expected)->update(['thumbnail_img'=>$new,'updated_at'=>now()]);
  if(!$changed&&$new!==(string)$expected)abort(409,'Thumbnail changed while converting. Refresh checker.');
  OptimizedImages::buildUpload((int)$new,null,'manual');
  // Gallery IDs, original upload and other products sharing it remain unchanged.
  foreach(['home_featured_products','home_best_selling_products','home_latest_products','todays_deal_products','products','products-category-'.$p->category_id] as $key)Cache::forget($key);
  $p->thumbnail_img=$new;return self::thumbnail($p);
 }
}
