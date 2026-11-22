<?php
namespace App\Services;
use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class StorefrontAppearance
{
    public const KEY='storefront_appearance_v1';
    public static function layouts()
    {
        return array_filter(config('storefront_themes.layouts',[]),function($theme){return !empty($theme['frame'])&&View::exists($theme['frame']);});
    }
    public static function page($name, $fallback)
    {
        $layout=self::settings()['layout'];
        $view='frontend.themes.'.$layout.'.'.$name;
        return in_array($layout,['studio','bazar'],true) && View::exists($view) ? $view : $fallback;
    }
    public static function colors(){return config('storefront_themes.colors',[]);}
    public static function settings()
    {
        $saved=Cache::remember(self::KEY,3600,function(){return json_decode((string)BusinessSetting::where('type',self::KEY)->value('value'),true)?:[];});
        return ['layout'=>isset(self::layouts()[$saved['layout']??''])?$saved['layout']:'classic','color'=>isset(self::colors()[$saved['color']??''])?$saved['color']:'existing'];
    }
    public static function palette($id=null)
    {
        $id=$id??self::settings()['color'];$pack=self::colors()[$id]??[];
        $primary=$pack['primary']??get_setting('base_color','#008A45');$hover=$pack['hover']??get_setting('base_hov_color','#006B35');
        return ['primary'=>self::hex($primary,'#008A45'),'hover'=>self::hex($hover,'#006B35')];
    }
    private static function hex($value,$fallback){return is_string($value)&&preg_match('/^#[0-9a-fA-F]{6}$/D',$value)?$value:$fallback;}
    public static function frame(){return self::layouts()[self::settings()['layout']]['frame'];}
    public static function stylesheet()
    {
        $path=self::layouts()[self::settings()['layout']]['stylesheet']??null;
        return is_string($path)&&preg_match('~^assets/css/themes/[A-Za-z0-9_-]+\.css$~D',$path)&&is_file(public_path($path))?$path:null;
    }
}
