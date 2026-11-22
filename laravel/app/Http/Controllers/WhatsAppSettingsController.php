<?php
namespace App\Http\Controllers;

use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class WhatsAppSettingsController extends Controller
{
    private const KEY='whatsapp_api_setup_v1';
    private const SECRET='whatsapp_api_credentials_v1';

    public function index()
    {
        abort_unless(auth()->user()->user_type==='admin',403);
        $options=json_decode(BusinessSetting::where('type',self::KEY)->value('value')??'{}',true)?:[];
        $stored=(bool)BusinessSetting::where('type',self::SECRET)->value('value');
        return view('backend.platform.whatsapp',compact('options','stored'));
    }

    public function save(Request $request)
    {
        abort_unless(auth()->user()->user_type==='admin',403);
        $data=$request->validate([
            'phone_number_id'=>['nullable','regex:/^[0-9]{5,30}$/D'],
            'business_account_id'=>['nullable','regex:/^[0-9]{5,30}$/D'],
            'sender_number'=>['nullable','regex:/^\\+?[0-9]{7,15}$/D'],
            'template_name'=>['nullable','regex:/^[a-z0-9_]{1,100}$/D'],
            'template_language'=>['required','regex:/^[a-z]{2,3}(?:_[A-Z]{2})?$/D'],
            'invoice_format'=>['required','in:link,pdf'],
            'access_token'=>['nullable','string','max:4096','regex:/^[A-Za-z0-9_.|\\-]+$/D'],
            'app_secret'=>['nullable','regex:/^[a-fA-F0-9]{32}$/D'],
            'verify_token'=>['nullable','regex:/^[A-Za-z0-9_\\-]{16,128}$/D'],
            'remove_credentials'=>['nullable','boolean'],
        ]);
        $changing=$request->boolean('remove_credentials')||$request->filled('access_token')||$request->filled('app_secret')||$request->filled('verify_token');
        if($changing&&!Hash::check((string)$request->input('password'),auth()->user()->password)){
            throw ValidationException::withMessages(['password'=>'Enter your current admin password to change API credentials.']);
        }
        DB::transaction(function()use($request,$data,$changing){
            $options=array_intersect_key($data,array_flip(['phone_number_id','business_account_id','sender_number','template_name','template_language','invoice_format']));
            // This is setup storage only: no send endpoint, webhook or order listener is enabled.
            $options['enabled']=false;
            $setting=BusinessSetting::where('type',self::KEY)->first()?:new BusinessSetting();$setting->type=self::KEY;$setting->value=json_encode($options);$setting->save();
            if($changing){
                $record=BusinessSetting::where('type',self::SECRET)->first()?:new BusinessSetting();$record->type=self::SECRET;
                if($request->boolean('remove_credentials')){$record->value=null;}
                else{
                    $secrets=$record->value?json_decode(Crypt::decryptString($record->value),true):[];
                    foreach(['access_token','app_secret','verify_token'] as $field)if($request->filled($field))$secrets[$field]=$data[$field];
                    $record->value=Crypt::encryptString(json_encode($secrets));
                }
                $record->save();
            }
        });
        \Illuminate\Support\Facades\Cache::forget('business_settings');
        return redirect()->route('platform.whatsapp')->with('platform_success','WhatsApp setup saved. Message sending remains disabled.');
    }
}
