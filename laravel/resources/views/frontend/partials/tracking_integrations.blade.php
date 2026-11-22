@unless(env('FOOTSTEP_MIGRATION_PREVIEW',false))
@php $managedTracking = \App\Services\TrackingIntegrations::settings(); @endphp
@if($managedTracking !== null)
<script>
(function(config){
    if(window.__footstepTrackingLoaded || navigator.globalPrivacyControl || navigator.doNotTrack === '1') return;
    window.__footstepTrackingLoaded=true;
    function script(src){var s=document.createElement('script');s.async=true;s.src=src;document.head.appendChild(s);}
    var ga=config.ga4||{},ads=config.google_ads||{},gtm=config.gtm||{},meta=config.meta||{},tik=config.tiktok||{};
    if((ga.enabled&&ga.id)||(ads.enabled&&ads.id)){
        window.dataLayer=window.dataLayer||[];window.gtag=window.gtag||function(){window.dataLayer.push(arguments);};
        gtag('js',new Date());
        script('https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(ga.enabled?ga.id:ads.id));
        if(ga.enabled&&ga.id)gtag('config',ga.id);
        if(ads.enabled&&ads.id)gtag('config',ads.id);
    }
    if(gtm.enabled&&gtm.id){window.dataLayer=window.dataLayer||[];window.dataLayer.push({'gtm.start':Date.now(),event:'gtm.js'});script('https://www.googletagmanager.com/gtm.js?id='+encodeURIComponent(gtm.id));}
    // Meta is initialized by the consent-aware ecommerce module.
    if(tik.enabled&&tik.id){
        window.TiktokAnalyticsObject='ttq';var ttq=window.ttq=window.ttq||[];
        ttq.methods=['page','track','identify','instances','debug','on','off','once','ready','alias','group','enableCookie','disableCookie'];
        ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)));};};
        for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);
        ttq.instance=function(id){var queue=(ttq._i||{})[id]||[];for(var j=0;j<ttq.methods.length;j++)ttq.setAndDefer(queue,ttq.methods[j]);return queue;};
        ttq.load=function(id,options){ttq._i=ttq._i||{};ttq._i[id]=[];ttq._i[id]._u='https://analytics.tiktok.com/i18n/pixel/events.js';ttq._t=ttq._t||{};ttq._t[id]=+new Date();ttq._o=ttq._o||{};ttq._o[id]=options||{};script('https://analytics.tiktok.com/i18n/pixel/events.js?sdkid='+encodeURIComponent(id)+'&lib=ttq');};
        ttq.load(tik.id);ttq.page();
    }
})(@json($managedTracking));
</script>
@endif

@endunless
