<?php
namespace App\Http\Controllers;
use App\Models\BusinessSetting;
use App\Services\StorefrontAppearance as Appearance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class AppearanceController extends Controller
{
    public function index()
    {
        abort_unless(auth()->user()->user_type==='admin',403);
        return view('backend.platform.appearance',['options'=>Appearance::settings(),'layouts'=>Appearance::layouts(),'packs'=>Appearance::colors()]);
    }
    public function saveBackend(Request $request)
    {
        abort_unless(auth()->user()->user_type==='admin',403);
        $data=$request->validate(['backend_theme'=>['required',Rule::in(['classic','insights'])]]);
        \Illuminate\Support\Facades\DB::transaction(function()use($data){
            \App\Services\BackendTheme::save($data['backend_theme']);
            if($data['backend_theme']==='insights')\App\Services\AdminDashboardAppearance::save('modern');
        });
        return redirect()->route('platform.appearance')->with('platform_success','Backend theme saved / অ্যাডমিন থিম সেভ হয়েছে।');
    }
    public function saveCredits(Request $request)
    {
        abort_unless(auth()->user()->user_type==='admin',403);
        $data=$request->validate(['developer_credit'=>'required|boolean']);
        $row=BusinessSetting::where('type','public_developer_credit_v1')->first()?:new BusinessSetting();
        $row->type='public_developer_credit_v1';$row->value=$request->boolean('developer_credit')?'1':'0';$row->save();
        Cache::forget('business_settings');
        return redirect()->route('platform.appearance')->with('platform_success','Developer footer saved / Footer সেভ হয়েছে।');
    }
    public function save(Request $request)
    {
        abort_unless(auth()->user()->user_type==='admin',403);
        $data=$request->validate(['action'=>['required',Rule::in(['save','reset'])],'layout'=>['required',Rule::in(array_keys(Appearance::layouts()))],'color'=>['required',Rule::in(array_keys(Appearance::colors()))]]);
        $options=$data['action']==='reset'?['layout'=>'classic','color'=>'existing']:['layout'=>$data['layout'],'color'=>$data['color']];
        $row=BusinessSetting::where('type',Appearance::KEY)->first()?:new BusinessSetting();$row->type=Appearance::KEY;$row->value=json_encode($options);$row->save();
        Cache::forget(Appearance::KEY);Cache::forget('business_settings');
        return redirect()->route('platform.appearance')->with('platform_success',$data['action']==='reset'?'Existing layout and brand colors restored.':'Layout and appearance saved.');
    }
}
