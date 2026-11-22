<?php
namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;

class BackendTheme
{
    const KEY='admin_backend_theme_v1';
    public static function current()
    {
        return Cache::remember(self::KEY,300,function(){
            return BusinessSetting::where('type',self::KEY)->value('value')==='insights'?'insights':'classic';
        });
    }
    public static function selected($request)
    {
        $theme=$request->query('backend_theme');
        return auth()->check() && auth()->user()->user_type==='admin' && in_array($theme,['classic','insights'],true) ? $theme : self::current();
    }
    public static function save($theme)
    {
        if(!in_array($theme,['classic','insights'],true))throw new \InvalidArgumentException('Unknown backend theme');
        $row=BusinessSetting::where('type',self::KEY)->first()?:new BusinessSetting();
        $row->type=self::KEY;$row->value=$theme;$row->save();
        Cache::forget(self::KEY);Cache::forget('business_settings');
    }
}
