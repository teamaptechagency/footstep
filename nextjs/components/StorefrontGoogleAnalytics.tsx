'use client';
import {useEffect} from 'react';
import {usePathname} from 'next/navigation';

type Config={id:string|null;require_consent:boolean};
export default function StorefrontGoogleAnalytics({config}:{config:Config}){
 const path=usePathname();
 useEffect(()=>{
  if(!config.id||!/^G-[A-Z0-9]{4,20}$/.test(config.id))return;
  const w=window as typeof window&{dataLayer?:unknown[];gtag?:(...args:unknown[])=>void;footstepGaConfigured?:string;footstepGaLastPath?:string};
  const privatePath=/^\/(admin|api|commerce|checkout|cart|users|shared-invoice|order-confirmed|order-track|purchase_history)(\/|$)/;
  if(privatePath.test(path))return;
  const choice=()=>document.cookie.split(';').map(s=>s.trim()).find(s=>s.startsWith('footstep_marketing='))?.split('=')[1];
  const allowed=()=>navigator.doNotTrack!=='1'&&!(navigator as Navigator&{globalPrivacyControl?:boolean}).globalPrivacyControl&&choice()!=='denied'&&(!config.require_consent||choice()==='granted');
  const send=()=>{
   if(!allowed()){w.gtag?.('consent','update',{analytics_storage:'denied',ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied'});return;}
   w.dataLayer=w.dataLayer||[];w.gtag=w.gtag||function(){w.dataLayer!.push(arguments);};
   if(w.footstepGaConfigured!==config.id){
    w.gtag('consent','default',{analytics_storage:'granted',ad_storage:'denied',ad_user_data:'denied',ad_personalization:'denied'});
    w.gtag('js',new Date());w.gtag('config',config.id,{send_page_view:false,allow_google_signals:false,allow_ad_personalization_signals:false});
    w.footstepGaConfigured=config.id!;
    if(!document.getElementById('footstep-ga4-script')){const script=document.createElement('script');script.id='footstep-ga4-script';script.async=true;script.src='https://www.googletagmanager.com/gtag/js?id='+encodeURIComponent(config.id!);document.head.append(script);}
   }else w.gtag('consent','update',{analytics_storage:'granted'});
   if(w.footstepGaLastPath!==path){w.gtag('event','page_view',{page_location:location.origin+path,page_title:document.title,send_to:config.id});w.footstepGaLastPath=path;}
  };
  send();window.addEventListener('focus',send);window.addEventListener('footstep:marketing-consent',send);
  return()=>{window.removeEventListener('focus',send);window.removeEventListener('footstep:marketing-consent',send);};
 },[path,config.id,config.require_consent]);return null;
}

