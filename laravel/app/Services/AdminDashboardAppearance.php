<?php
namespace App\Services;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
class AdminDashboardAppearance {
    const KEY='admin_dashboard_layout_v1';
    public static function current(){return Cache::remember(self::KEY,300,function(){return BusinessSetting::where('type',self::KEY)->value('value')==='modern'?'modern':'classic';});}
    public static function selected($request){$preview=$request->query('dashboard_layout');return auth()->user()->user_type==='admin'&&in_array($preview,['classic','modern'],true)?$preview:self::current();}
    public static function save($layout){$row=BusinessSetting::where('type',self::KEY)->first()?:new BusinessSetting();$row->type=self::KEY;$row->value=$layout;$row->save();Cache::forget(self::KEY);Cache::forget('business_settings');}
}
