<?php
namespace App\Http\Controllers;

use App\Services\ProductSizeGuide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductSizeGuideController extends Controller
{
    private function authorizeAdmin(){abort_unless(auth()->user()&&auth()->user()->user_type==='admin',403);}
    public function index(){ $this->authorizeAdmin();$guide=ProductSizeGuide::settings();return view('backend.website_settings.size_guide',compact('guide')); }
    public function save(Request $request)
    {
        $this->authorizeAdmin();$data=$request->validate(['instructions_title'=>'sometimes|required|string|max:100','video_title'=>'sometimes|nullable|string|max:100','image_caption'=>'sometimes|nullable|string|max:160','steps_text'=>'sometimes|nullable|string|max:4000','fit_tips_text'=>'sometimes|nullable|string|max:2500','extra_info'=>'sometimes|nullable|string|max:4000','title'=>'required|string|max:100','intro'=>'required|string|max:1500','image_id'=>'nullable|integer|exists:uploads,id','youtube_url'=>'nullable|string|max:500','measurements'=>'required|array|size:7','measurements.*'=>'required|numeric|min:15|max:40']);
        if(array_diff(array_keys($data['measurements']),array_keys(ProductSizeGuide::measurements())))throw ValidationException::withMessages(['measurements'=>'শুধু 39 থেকে 45 সাইজের মাপ দিন।']);
        if(!empty($data['youtube_url'])&&!ProductSizeGuide::youtubeId($data['youtube_url']))throw ValidationException::withMessages(['youtube_url'=>'একটি সঠিক https YouTube video link দিন। Playlist link গ্রহণ করা হবে না।']);
        if(!empty($data['image_id'])){$extension=DB::table('uploads')->where('id',$data['image_id'])->value('extension');if(!in_array(strtolower($extension),['jpg','jpeg','png','webp','gif','bmp','avif'],true))throw ValidationException::withMessages(['image_id'=>'Media Center থেকে image বাছুন।']);}
        $previous=0;foreach(ProductSizeGuide::measurements() as $size=>$unused){$cm=(float)$data['measurements'][$size];if($cm<=$previous)throw ValidationException::withMessages(['measurements'=>'বড় সাইজের মাপ আগের সাইজের চেয়ে বড় হতে হবে।']);$data['measurements'][$size]=$cm;$previous=$cm;}
        $data['image_id']=$data['image_id']??null;$data['youtube_url']=$data['youtube_url']??'';
        foreach(['steps_text'=>['steps',12,300],'fit_tips_text'=>['fit_tips',10,250]] as $field=>$rule){
            if(!array_key_exists($field,$data))continue;
            $lines=array_values(array_filter(array_map('trim',preg_split('/\r\n|\r|\n/',(string)$data[$field])),function($line){return $line!=='';}));
            if(count($lines)>$rule[1]||array_filter($lines,function($line)use($rule){return mb_strlen($line)>$rule[2];}))throw ValidationException::withMessages([$field=>'ধাপ/টিপস কমিয়ে লিখুন। সর্বোচ্চ '.$rule[1].' লাইন, প্রতি লাইনে '.$rule[2].' অক্ষর।']);
            $data[$rule[0]]=$lines;unset($data[$field]);
        }
        foreach(['video_title','image_caption','extra_info'] as $field){if(array_key_exists($field,$data))$data[$field]=(string)$data[$field];}
        $data=array_replace(ProductSizeGuide::settings(),$data);
        DB::table('business_settings')->updateOrInsert(['type'=>ProductSizeGuide::KEY],['value'=>json_encode($data,JSON_UNESCAPED_UNICODE),'updated_at'=>now()]);
        return back()->with('success','Size Guide সংরক্ষিত হয়েছে। Website refresh করলে দেখা যাবে।');
    }
}
