<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use App\Services\HomepageSections;

class HomepageSectionsController extends Controller
{
    private function authorizeAdmin(){abort_unless(auth()->user()&&auth()->user()->user_type==='admin',403);}

    public function index()
    {
        $this->authorizeAdmin();$sections=HomepageSections::settings();
        $products=DB::table('products')->leftJoin('uploads','uploads.id','=','products.thumbnail_img')->select('products.id','products.name','uploads.file_name as image_file')->orderBy('products.name')->get();
        foreach($products as $product){
            $manifest=$product->image_file?\App\Services\ImageVariantManifest::read($product->image_file):[];
            $path=$manifest['variants'][160]??$product->image_file;
            $url=$path?my_asset($path):static_asset('assets/img/placeholder.jpg');
            $product->picker_html='<span class="home-product-option"><img src="'.e($url).'" alt="" width="30" height="38"><span>#'.$product->id.' · '.e($product->name).'</span></span>';
        }
        $brands=DB::table('brands')->orderBy('name')->get(['id','name']);
        $categories=DB::table('categories')->orderBy('name')->get(['id','name']);
        return view('backend.marketing.homepage_sections',compact('sections','products','brands','categories'));
    }

    public function save(Request $request)
    {
        $this->authorizeAdmin();
        $key=$request->validate(['section_key'=>'nullable|in:new_arrivals,running,sneakers,best_selling'])['section_key']??null;
        if($key)$request->merge(['sections'=>[$key=>$request->input('sections.'.$key)]]);
        $data=$request->validate([
            'sections'=>'required|array|size:'.($key?1:4),'sections.*'=>'required|array',
            'sections.*.title'=>'required|string|max:80','sections.*.position'=>'required|integer|min:1|max:4|distinct',
            'sections.*.enabled'=>'required|boolean','sections.*.auto_slide'=>'required|boolean',
            'sections.*.seconds'=>'required|integer|min:2|max:60','sections.*.limit'=>'required|integer|min:1|max:40',
            'sections.*.source'=>'required|in:latest,best,category,brand,manual',
            'sections.*.brand_id'=>'nullable|integer|exists:brands,id','sections.*.category_id'=>'nullable|integer|exists:categories,id',
            'sections.*.product_ids'=>'nullable|array|max:40','sections.*.product_ids.*'=>'integer|min:1|exists:products,id',
        ])['sections'];
        if(array_diff(array_keys($data),array_keys(HomepageSections::defaults())))abort(422);
        foreach($data as $dataKey=>&$section){
            if($section['source']==='category'&&empty($section['category_id']))throw ValidationException::withMessages(['sections.'.$dataKey.'.category_id'=>'Category বাছুন।']);
            if($section['source']==='brand'&&empty($section['brand_id']))throw ValidationException::withMessages(['sections.'.$dataKey.'.brand_id'=>'Brand বাছুন।']);
            $section['product_ids']=array_values(array_unique(array_map('intval',$section['product_ids']??[])));
            $section['enabled']=(bool)$section['enabled'];$section['auto_slide']=(bool)$section['auto_slide'];
        }unset($section);
        try{
            $saved=Cache::lock('homepage_sections_save',10)->block(3,function()use($data,$key){
                return DB::transaction(function()use($data,$key){
                    DB::table('business_settings')->where('type',HomepageSections::KEY)->lockForUpdate()->first();
                    if($key){
                        $saved=HomepageSections::settings();$oldPosition=(int)$saved[$key]['position'];$targetPosition=(int)$data[$key]['position'];
                        foreach($saved as $otherKey=>&$other){if($otherKey!==$key&&(int)$other['position']===$targetPosition)$other['position']=$oldPosition;}unset($other);
                        $saved[$key]=$data[$key];
                    }else $saved=$data;
                    DB::table('business_settings')->updateOrInsert(['type'=>HomepageSections::KEY],['value'=>json_encode($saved,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]);
                    return $saved;
                });
            });
        }catch(\Illuminate\Contracts\Cache\LockTimeoutException $e){throw ValidationException::withMessages(['sections'=>'অন্য একটি Save চলছে। কয়েক সেকেন্ড পরে আবার চেষ্টা করুন।']);}
        Cache::forget('business_settings');
        if($request->expectsJson())return response()->json(['success'=>true,'section_key'=>$key,'message'=>$key?'এই section সংরক্ষিত হয়েছে।':'সব section সংরক্ষিত হয়েছে।','positions'=>array_map(function($section){return (int)$section['position'];},$saved)])->header('Cache-Control','no-store');
        return redirect()->route('homepage-sections.index')->with('success','Homepage sections সংরক্ষিত হয়েছে। Website refresh করলে পরিবর্তন দেখবেন।');
    }
}
