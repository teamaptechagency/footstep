<?php
namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Models\Product;
use App\Services\MetaPixel;
use App\Services\MetaEventData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class MetaPixelController extends Controller
{
    private function admin(){abort_unless(auth()->check()&&auth()->user()->user_type==='admin',403);}

    public function index()
    {
        $this->admin();$meta=app(MetaPixel::class);$options=MetaPixel::settings();
        $catalogUrl=!empty($options['catalog_enabled'])?route('meta.catalog',['key'=>$meta->catalogKey()]):null;
        return view('backend.platform.meta',['options'=>$options,'diagnostics'=>$meta->diagnostics(),'catalogUrl'=>$catalogUrl]);
    }

    public function save(Request $request)
    {
        $this->admin();
        $request->validate(['id'=>['nullable','string','regex:/^[0-9]{5,25}$/'],'token'=>['nullable','string','max:4096','regex:/^[A-Za-z0-9_|.\-]{20,4096}$/'],'test_code'=>'nullable|alpha_dash|max:100','graph_version'=>['required','regex:/^v[0-9]{1,3}\.0$/'],'cod_event'=>'required|in:Purchase,Lead','dispatch_limit'=>'nullable|integer|min:1|max:100']);
        if($request->boolean('enabled')&&!$request->filled('id'))throw ValidationException::withMessages(['id'=>'Pixel ID is required.']);
        $meta=app(MetaPixel::class);
        $token=trim((string)$request->token);
        if($request->boolean('capi')&&(!$meta->hasToken()||$request->boolean('remove_token'))&&$token==='')throw ValidationException::withMessages(['token'=>'Enter a Conversions API access token before enabling CAPI.']);
        if($token!==''||$request->boolean('remove_token')){
            if(!Hash::check((string)$request->password,auth()->user()->password))throw ValidationException::withMessages(['password'=>'Confirm your admin password to change the CAPI token.']);
            if($token!=='')$meta->setToken($token);elseif($request->boolean('remove_token'))$meta->removeToken();
        }
        $data=['id'=>trim((string)$request->id),'test_code'=>trim((string)$request->test_code),'graph_version'=>$request->graph_version,'cod_event'=>$request->cod_event,'dispatch_limit'=>max(1,min(100,(int)($request->input('dispatch_limit')?:10)))];
        foreach(['enabled','browser','capi','advanced_matching','require_consent','exclude_admin','catalog_enabled'] as $key)$data[$key]=$request->boolean($key);
        foreach(MetaEventData::EVENTS as $event)$data['events'][$event]=$request->boolean('events.'.$event);
        $row=BusinessSetting::where('type',MetaPixel::KEY)->first()?:new BusinessSetting();$row->type=MetaPixel::KEY;$row->value=json_encode($data);$row->save();
        Cache::forget('meta_pixel_v2');Cache::forget('business_settings');
        return redirect()->route('platform.meta')->with('platform_success','Meta settings saved. Token is stored encrypted outside the website root.');
    }

    public function event(Request $request)
    {
        $request->validate(['ticket'=>'required|string|max:20000']);
        try{$result=app(MetaPixel::class)->queueTicket($request->ticket,$request);}catch(\RuntimeException $e){return response()->json(['status'=>'rejected'],422);}catch(\Throwable $e){return response()->json(['status'=>'temporarily_unavailable'],202);}
        return response()->json(['status'=>$result],202);
    }

    public function consent(Request $request)
    {
        $request->validate(['choice'=>'required|in:granted,denied']);
        if($request->choice==='denied')app(MetaPixel::class)->revoke($request->session()->getId());
        return response()->json(['choice'=>$request->choice])->cookie('footstep_marketing',$request->choice,60*24*180,'/',null,$request->secure(),false,false,'lax');
    }

    public function test(Request $request)
    {
        $this->admin();$options=MetaPixel::settings();$meta=app(MetaPixel::class);
        if(empty($options['enabled'])||empty($options['capi'])||empty($options['test_code'])||!$meta->hasToken())throw ValidationException::withMessages(['test_code'=>'Enable CAPI and enter a Meta Test Events code first.']);
        $id='test-'.bin2hex(random_bytes(16));
        $meta->enqueue(['event_name'=>'PageView','event_id'=>$id,'event_time'=>time(),'event_source_url'=>MetaEventData::safeUrl(route('home')),'action_source'=>'website','user_data'=>['external_id'=>[hash('sha256',$id)]],'custom_data'=>[]]);
        return back()->with('platform_success','Test event queued. Run the worker; check Meta Events Manager → Test Events. No customer record was used.');
    }

    public function catalog(Request $request,$key)
    {
        $meta=app(MetaPixel::class);$options=MetaPixel::settings();
        abort_unless(!empty($options['catalog_enabled'])&&preg_match('/^[a-f0-9]{64}$/D',$key)&&hash_equals($meta->catalogKey(),$key),404);
        $currency=get_system_default_currency()->code;
        return response()->stream(function()use($currency){
            $out=fopen('php://output','w');fputcsv($out,['id','title','description','availability','condition','price','sale_price','link','image_link','brand']);
            Product::with(['stocks','brand'])->where('published',1)->where('approved',1)->where('auction_product',0)->where('digital',0)->orderBy('id')->chunkById(100,function($products)use($out,$currency){
                foreach($products as $product){$image=uploaded_asset($product->thumbnail_img);if(!$image)continue;$price=(float)$product->unit_price;$sale=$price;$active=$product->discount_start_date===null||(time()>=$product->discount_start_date&&time()<=$product->discount_end_date);if($active)$sale=$product->discount_type==='percent'?$price*(1-(float)$product->discount/100):$price-(float)$product->discount;
                    $title=mb_substr(strip_tags($product->name),0,150);$description=mb_substr(trim(html_entity_decode(strip_tags($product->description))),0,5000)?:$title;
                    if(preg_match('/^[=+@-]/',$title))$title="'".$title;if(preg_match('/^[=+@-]/',$description))$description="'".$description;
                    fputcsv($out,[(string)$product->id,$title,$description,$product->stocks->sum('qty')>0?'in stock':'out of stock','new',number_format(max(0,$price),2,'.','').' '.$currency,$sale<$price?number_format(max(0,$sale),2,'.','').' '.$currency:'',route('product',$product->slug),$image,$product->brand?$product->brand->name:'Footstep']);
                }
            });fclose($out);
        },200,['Content-Type'=>'text/csv; charset=UTF-8','Cache-Control'=>'private, no-store','X-Robots-Tag'=>'noindex']);
    }

    public function retry(Request $request)
    {
        $this->admin();$request->validate(['key'=>['required','regex:/^[a-f0-9]{64}$/']]);
        try{app(MetaPixel::class)->retry($request->key);}catch(\Throwable $e){throw ValidationException::withMessages(['retry'=>'This failed event is expired, invalid or no longer belongs to the configured pixel.']);}
        return back()->with('platform_success','Event requeued with its original event ID for deduplication.');
    }
}
