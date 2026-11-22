<?php
namespace App\Http\Controllers;

use App\Services\{MetaPixel,MetaEventData,StorefrontV3Catalog};
use Illuminate\Http\Request;

/** Session-bound public event context. Never accepts prices or event names from clients. */
class StorefrontMetaController extends Controller
{
    private function publicRequest(Request $request,$path,$method='GET')
    {
        $public=Request::create($request->getSchemeAndHttpHost().$path,$method,[],$request->cookies->all(),[],['REMOTE_ADDR'=>$request->ip()]);
        $public->headers->replace($request->headers->all());$public->setLaravelSession($request->session());$public->setUserResolver($request->getUserResolver());return $public;
    }
    public function event(Request $request)
    {
        $data=$request->validate(['ticket'=>'required|string|max:20000']);
        try {$result=app(MetaPixel::class)->queueTicket($data['ticket'],$this->publicRequest($request,'/','POST'));}
        catch(\RuntimeException $e){return response()->json(['status'=>'rejected'],422);}
        catch(\Throwable $e){return response()->json(['status'=>'temporarily_unavailable'],202);}
        return response()->json(['status'=>$result],202)->header('Cache-Control','private, no-store');
    }
    public function page(Request $request)
    {
        $data=$request->validate(['path'=>'required|string|max:300','view'=>'required|regex:/^[a-f0-9]{32}$/D','search'=>'nullable|string|max:100']);
        $path=$data['path'];
        abort_unless(preg_match('~^/(?:$|shop$|search$|product/[a-zA-Z0-9_-]+$|category/[a-zA-Z0-9_-]+$|brand/[a-zA-Z0-9_-]+$|categories$|brands$|flash-deals(?:/[a-zA-Z0-9_-]+)?$|flash-deal/[a-zA-Z0-9_-]+$|size-guide$)~D',$path),422);
        $public=$this->publicRequest($request,$path);
        $original=app('request');$resolver=$request->getUserResolver();app()->instance('request',$public);$public->setUserResolver($resolver);
        try {
            $meta=app(MetaPixel::class);$config=$meta->browserConfig($public);$events=[];
            if($config['enabled']&&$meta->allowed($public)) {
                $stable='next-'.$data['view'].'-'.$path;
                $events[]=MetaPixel::event('PageView',[],$stable);
                if(preg_match('~^/product/([^/]+)$~D',$path,$m)) {
                    $product=StorefrontV3Catalog::query()->where(function($q)use($m){$q->where('slug',$m[1]);if(ctype_digit($m[1]))$q->orWhere('id',(int)$m[1]);})->first();
                    if($product){$money=MetaEventData::commerce([['id'=>$product->id,'quantity'=>1,'price'=>home_discounted_base_price($product,false)]],MetaPixel::currency());$money['content_name']=mb_substr($product->name,0,200);$events[]=MetaPixel::event('ViewContent',$money,$stable);}
                }
                if(in_array($path,['/shop','/search'],true)&&!empty($data['search']))$events[]=MetaPixel::event('Search',['search_string'=>MetaEventData::searchTerm($data['search'])],$stable);
                $config['contact_event']=MetaPixel::event('Contact',['content_name'=>'WhatsApp / Messenger'],null,[],null,false);
            }
            // Respect privacy choices before loading the third-party browser script.
            $config['available']=(bool)$config['enabled']&&$public->header('DNT')!=='1'&&$public->header('Sec-GPC')!=='1';$config['privacy_reason']=$public->user()&&!empty($config['enabled'])===false&&!empty(MetaPixel::settings()['exclude_admin'])&&in_array($public->user()->user_type,['admin','staff'],true)?'admin_excluded':($public->header('DNT')==='1'||$public->header('Sec-GPC')==='1'?'browser_privacy':($config['consent']!=='granted'?'consent_required':''));if(!$meta->allowed($public))$config['enabled']=false;
            $config['endpoint']=route('meta.next.event');$config['consent_endpoint']=route('meta.next.consent');$config['csrf']=csrf_token();
            return response()->json(['config'=>$config,'events'=>array_values(array_filter($events))])->header('Cache-Control','private, no-store')->header('Vary','Cookie');
        } finally {app()->instance('request',$original);}
    }
}
