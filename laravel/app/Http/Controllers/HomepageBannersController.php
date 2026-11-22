<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Services\HomepageBanners;

class HomepageBannersController extends Controller
{
    private function authorizeAdmin(){abort_unless(auth()->user()&&auth()->user()->user_type==='admin',403);}
    public function index()
    {
        $this->authorizeAdmin();$settings=HomepageBanners::settings();
        $items=collect($settings['items'])->sortBy('position')->values();
        return view('backend.marketing.homepage_banners',compact('settings','items'));
    }
    public function save(Request $request)
    {
        $this->authorizeAdmin();
        $data=$request->validate(['id'=>'nullable|regex:/^[a-zA-Z0-9-]{1,50}$/D','title'=>'required|string|max:100','alt'=>'nullable|string|max:160','image_id'=>'required|integer|exists:uploads,id','mobile_image_id'=>'nullable|integer|exists:uploads,id','href'=>'nullable|string|max:1000','position'=>'required|integer|min:1|max:999','enabled'=>'required|boolean','new_tab'=>'required|boolean','starts_at'=>'nullable|date_format:Y-m-d\TH:i','ends_at'=>'nullable|date_format:Y-m-d\TH:i']);
        if(!empty($data['href'])&&HomepageBanners::safeLink($data['href'])!==trim($data['href']))throw ValidationException::withMessages(['href'=>'Website path (/shop) অথবা পূর্ণ http/https link দিন।']);
        foreach(['image_id','mobile_image_id'] as $field){if(empty($data[$field]))continue;$extension=DB::table('uploads')->where('id',$data[$field])->value('extension');if(!in_array(strtolower($extension),['jpg','jpeg','png','webp','gif','bmp','avif'],true))throw ValidationException::withMessages([$field=>'Media Center থেকে একটি image বাছুন।']);}
        if(!empty($data['starts_at'])&&!empty($data['ends_at'])&&$data['ends_at']<=$data['starts_at'])throw ValidationException::withMessages(['ends_at'=>'শেষ সময় শুরুর সময়ের পরে হতে হবে।']);
        $data['id']=($data['id']??null)?:Str::uuid()->toString();$data['href']=HomepageBanners::safeLink($data['href']??'/shop');$data['alt']=$data['alt']??'';$data['mobile_image_id']=$data['mobile_image_id']??null;
        foreach(['enabled','new_tab'] as $key)$data[$key]=(bool)$data[$key];
        HomepageBanners::mutate(function($settings)use($data){$found=false;foreach($settings['items'] as &$item){if($item['id']===$data['id']){$item=$data;$found=true;break;}}unset($item);if(!$found){abort_if(count($settings['items'])>=30,422,'সর্বোচ্চ ৩০টি banner রাখা যাবে।');$settings['items'][]=$data;}return $settings;});
        return back()->with('success','Banner সংরক্ষিত হয়েছে। Homepage refresh করলে সময়সূচি অনুযায়ী দেখাবে।');
    }
    public function options(Request $request)
    {
        $this->authorizeAdmin();$data=$request->validate(['auto_slide'=>'required|boolean','seconds'=>'required|integer|min:2|max:60']);
        HomepageBanners::mutate(function($settings)use($data){$settings['auto_slide']=(bool)$data['auto_slide'];$settings['seconds']=(int)$data['seconds'];return $settings;});
        return back()->with('success','Slide settings সংরক্ষিত হয়েছে।');
    }
    public function destroy(Request $request)
    {
        $this->authorizeAdmin();$data=$request->validate(['id'=>'required|string|max:50']);
        HomepageBanners::mutate(function($settings)use($data){$settings['items']=array_values(array_filter($settings['items'],function($item)use($data){return $item['id']!==$data['id'];}));return $settings;});
        return back()->with('success','Banner তালিকা থেকে সরানো হয়েছে। Media Center-এর original image মুছেনি।');
    }
}
