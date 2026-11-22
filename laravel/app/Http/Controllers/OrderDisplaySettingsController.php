<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Services\OrderDisplay;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
class OrderDisplaySettingsController extends Controller {
    public function save(Request $request){
        abort_unless(auth()->check()&&auth()->user()->user_type==='admin',403);
        $data=$request->validate(['order_display_format'=>'required|in:full,last_phone']);
        $row=BusinessSetting::where('type',OrderDisplay::KEY)->first()?:new BusinessSetting();$row->type=OrderDisplay::KEY;$row->value=$data['order_display_format'];$row->save();Cache::forget(OrderDisplay::KEY);
        return redirect()->route('platform.appearance')->with('platform_success','Order number format updated for confirmation and invoices. / অর্ডার নম্বরের format সেভ হয়েছে।');
    }
}