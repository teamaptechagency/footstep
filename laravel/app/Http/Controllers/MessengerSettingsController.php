<?php
namespace App\Http\Controllers;
use App\Models\BusinessSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class MessengerSettingsController extends Controller {
    private const OPTIONS='messenger_api_setup_v1';
    private const SECRETS='messenger_api_credentials_v1';
    private const PRIVATE_FIELDS=['access_token','app_secret','verify_token','password','_token'];
    public function index(){
        abort_unless(auth()->user()->user_type==='admin',403);
        $options=json_decode(BusinessSetting::where('type',self::OPTIONS)->value('value')??'{}',true)?:[];
        $stored=(bool)BusinessSetting::where('type',self::SECRETS)->value('value');
        return view('backend.platform.messenger',compact('options','stored'));
    }
    public function save(Request $request){
        abort_unless(auth()->user()->user_type==='admin',403);
        $validator=Validator::make($request->all(),[
            'page_id'=>['nullable','regex:/^[0-9]{5,30}$/D'],
            'app_id'=>['nullable','regex:/^[0-9]{5,30}$/D'],
            'page_username'=>['nullable','regex:/^[A-Za-z0-9.]{1,100}$/D'],
            'access_token'=>['nullable','string','max:4096','regex:/^[A-Za-z0-9_.|\-]+$/D'],
            'app_secret'=>['nullable','regex:/^[a-fA-F0-9]{32}$/D'],
            'verify_token'=>['nullable','regex:/^[A-Za-z0-9_\-]{16,128}$/D'],
            'remove_credentials'=>'nullable|boolean',
            'include_name'=>'nullable|boolean','include_size'=>'nullable|boolean','include_price'=>'nullable|boolean','include_link'=>'nullable|boolean'
        ]);
        if($validator->fails())return back()->withErrors($validator)->withInput($request->except(self::PRIVATE_FIELDS));
        $data=$validator->validated();$changing=$request->boolean('remove_credentials')||$request->filled('access_token')||$request->filled('app_secret')||$request->filled('verify_token');
        if($changing&&!Hash::check((string)$request->input('password'),auth()->user()->password))return back()->withErrors(['password'=>'Enter your current admin password to change credentials.'])->withInput($request->except(self::PRIVATE_FIELDS));
        DB::transaction(function()use($request,$data,$changing){
            $options=array_intersect_key($data,array_flip(['page_id','app_id','page_username']));
            foreach(['include_name','include_size','include_price','include_link'] as $key)$options[$key]=$request->boolean($key);
            // Reserved setup only. No webhook or Messenger message sender is activated.
            $options['enabled']=false;$options['status']='draft';
            $record=BusinessSetting::where('type',self::OPTIONS)->first()?:new BusinessSetting();$record->type=self::OPTIONS;$record->value=json_encode($options);$record->save();
            if($changing){
                $record=BusinessSetting::where('type',self::SECRETS)->first()?:new BusinessSetting();$record->type=self::SECRETS;
                if($request->boolean('remove_credentials'))$record->value=null;
                else{$secret=$record->value?json_decode(Crypt::decryptString($record->value),true):[];foreach(['access_token','app_secret','verify_token'] as $field)if($request->filled($field))$secret[$field]=$data[$field];$record->value=Crypt::encryptString(json_encode($secret));}
                $record->save();
            }
        });
        \Illuminate\Support\Facades\Cache::forget('business_settings');
        return redirect()->route('platform.messenger')->with('platform_success','Messenger setup saved. Integration is a draft and remains disabled. / সেটিং সেভ হয়েছে; সংযোগ এখনো চালু হয়নি।');
    }
}
