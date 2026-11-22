<?php
namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use App\Services\TrackingIntegrations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class TrackingIntegrationsController extends Controller
{
    private function authorizeAdmin()
    {
        abort_unless(auth()->user() && auth()->user()->user_type === 'admin', 403);
    }

    public function index()
    {
        $this->authorizeAdmin();
        return view('backend.platform.tracking', ['cards'=>TrackingIntegrations::cards()]);
    }

    private function details($provider)
    {
        $details=config('tracking_providers.'.$provider);
        abort_unless(in_array($provider,['tiktok','ga4','gtm','google_ads'],true) && is_array($details),404);
        return $details;
    }

    public function provider($provider)
    {
        $this->authorizeAdmin();
        $details=$this->details($provider);
        return view('backend.platform.tracking_provider',[
            'provider'=>$provider,'details'=>$details,'card'=>TrackingIntegrations::cards()[$provider],
        ]);
    }

    private function validated(Request $request, $provider)
    {
        $details=$this->details($provider);
        $definition=TrackingIntegrations::definitions()[$provider];
        foreach(array_merge(['id'],array_keys($details['fields'])) as $key){
            if(is_string($request->input($key)))$request->merge([$key=>trim($request->input($key))]);
        }
        $rules=['enabled'=>'required|boolean','id'=>['nullable','string','max:64','regex:'.$definition['pattern']]];
        foreach($details['fields'] as $key=>$field){
            $rules[$key]=['nullable','string','max:'.$field['max']];
            if($field['pattern'])$rules[$key][]='regex:'.$field['pattern'];
        }
        $data=$request->validate($rules);
        $data['id']=trim($data['id']??'');
        $data['enabled']=$request->boolean('enabled');
        if($data['enabled'] && $data['id']===''){
            throw \Illuminate\Validation\ValidationException::withMessages(['id'=>'Enable করার আগে সঠিক '.$definition['label'].' ID দিন।']);
        }
        foreach($details['fields'] as $key=>$field)$data[$key]=trim($data[$key]??'');
        return $data;
    }

    private function persist(array $updates)
    {
        // Materialize legacy IDs only on first managed save; retain all other providers.
        $baseline=[];
        foreach(TrackingIntegrations::cards() as $key=>$card){
            $baseline[$key]=['id'=>$card['id'],'enabled'=>(bool)$card['enabled']];
        }
        DB::transaction(function()use($updates,$baseline){
            $setting=BusinessSetting::where('type',TrackingIntegrations::KEY)->lockForUpdate()->first();
            $stored=$setting?json_decode($setting->value,true):null;
            $settings=is_array($stored)?$stored:$baseline;
            foreach($updates as $provider=>$data){
                $settings[$provider]=array_replace($settings[$provider]??[],$data);
            }
            $setting=$setting?:new BusinessSetting();
            $setting->type=TrackingIntegrations::KEY;
            $setting->value=json_encode($settings);
            $setting->save();
        });
        Cache::forget('tracking_integrations_v1');
        Cache::forget('business_settings');
    }

    public function saveProvider(Request $request,$provider)
    {
        $this->authorizeAdmin();
        $data=$this->validated($request,$provider);
        $this->persist([$provider=>$data]);
        return redirect()->route('platform.tracking.provider',$provider)->with('platform_success','Settings saved / সংরক্ষিত হয়েছে। অন্য integrations বদলায়নি। Event receipt provider-এর test tool-এ যাচাই করুন।');
    }

    public function save(Request $request)
    {
        $this->authorizeAdmin();
        // Compatibility for an older open settings tab: update only submitted providers.
        $updates=[];
        foreach(['tiktok','ga4','gtm','google_ads'] as $provider){
            if(!$request->has($provider))continue;
            $input=$request->input($provider);
            abort_unless(is_array($input),422);
            $input['enabled']=$input['enabled']??'0';
            $updates[$provider]=$this->validated(Request::create('/','POST',$input),$provider);
        }
        if($updates)$this->persist($updates);
        return redirect()->route('platform.tracking')->with('platform_success','Tracking settings saved. Verify receipt in the provider testing tool.');
    }
}
