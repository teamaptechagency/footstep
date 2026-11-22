<?php
namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;

class TrackingIntegrations
{
    const KEY = 'storefront_tracking_v1';

    public static function definitions()
    {
        return [
            'meta' => ['label'=>'Meta Pixel', 'icon'=>'f', 'color'=>'#1877f2', 'pattern'=>'/^[0-9]{5,25}$/', 'placeholder'=>'Pixel ID'],
            'tiktok' => ['label'=>'TikTok Ads', 'icon'=>'♪', 'color'=>'#111827', 'pattern'=>'/^[A-Za-z0-9]{8,40}$/', 'placeholder'=>'Pixel ID'],
            'ga4' => ['label'=>'Google Analytics 4', 'icon'=>'▥', 'color'=>'#f59e0b', 'pattern'=>'/^G-[A-Z0-9]{4,20}$/', 'placeholder'=>'G-XXXXXXXXXX'],
            'gtm' => ['label'=>'Tag Manager (GTM)', 'icon'=>'◆', 'color'=>'#0284c7', 'pattern'=>'/^GTM-[A-Z0-9]{4,20}$/', 'placeholder'=>'GTM-XXXXXXX'],
            'google_ads' => ['label'=>'Google Ads', 'icon'=>'G', 'color'=>'#10b981', 'pattern'=>'/^AW-[0-9]{5,20}$/', 'placeholder'=>'AW-123456789'],
        ];
    }

    public static function settings()
    {
        return Cache::remember('tracking_integrations_v1', 3600, function () {
            $value = BusinessSetting::where('type', self::KEY)->value('value');
            return $value === null ? null : json_decode($value, true);
        });
    }

    public static function cards()
    {
        $settings = self::settings();
        $legacy = [
            'meta'=>['id'=>(string)env('FACEBOOK_PIXEL_ID',''),'enabled'=>get_setting('facebook_pixel') == 1],
            'ga4'=>['id'=>(string)env('TRACKING_ID',''),'enabled'=>get_setting('google_analytics') == 1],
        ];
        $cards = [];
        foreach (self::definitions() as $key => $definition) {
            $item = $settings[$key] ?? ($settings === null ? ($legacy[$key] ?? ['id'=>'','enabled'=>false]) : ['id'=>'','enabled'=>false]);
            if($settings === null && !preg_match($definition['pattern'],$item['id'] ?? '')) $item=['id'=>'','enabled'=>false];
            if($key === 'meta'){$meta=MetaPixel::settings();$item=['id'=>$meta['id'],'enabled'=>$meta['enabled']];}
            if(env('FOOTSTEP_MIGRATION_PREVIEW',false))$item['enabled']=false;
            $configured = !empty($item['enabled']) && preg_match($definition['pattern'], $item['id'] ?? '');
            $cards[$key] = $definition + $item + ['configured'=>(bool)$configured,'legacy'=>$key !== 'meta' && $settings===null];
        }
        return $cards;
    }

    public static function filterLegacyScripts($html)
    {
        $html = preg_replace_callback('~<(script|noscript)\b[^>]*>.*?</\1\s*>~is', function($match){return preg_match('~fbq\s*\(|fbevents\.js|facebook\.com/tr\?~i',$match[0]) ? '' : $match[0];},(string)$html);
        if (self::settings() === null) return $html;
        return self::removeTrackingSnippets((string)$html);
    }

    public static function removeTrackingSnippets($html)
    {
        // Suppress only recognizable provider tags at render time. Stored custom
        // content is preserved so switching/rollback does not destroy it.
        return preg_replace_callback('~<(script|noscript)\b[^>]*>.*?</\1\s*>~is', function ($match) {
            return preg_match('~fbq\s*\(|ttq\s*[.\[]|gtag\s*\(|googletagmanager\.com/|fbevents\.js|analytics\.tiktok\.com/|TiktokAnalyticsObject|facebook\.com/tr\?~i',$match[0]) ? '' : $match[0];
        }, $html);
    }
}
