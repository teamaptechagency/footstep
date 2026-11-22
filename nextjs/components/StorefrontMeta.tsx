'use client';
import {Suspense,useEffect,useRef,useState} from 'react';
import {usePathname,useSearchParams} from 'next/navigation';
type MetaEvent={name:string;id:string;data:Record<string,unknown>;ticket?:string;stable?:boolean};
type Config={enabled:boolean;available?:boolean;consent?:string;privacy_reason?:string;consent_endpoint?:string;csrf?:string;[key:string]:unknown};
type MetaWindow=Window & {FootstepMetaConfig?:Config;FootstepMetaEvents?:MetaEvent[];FootstepMeta?:{emit:(event:MetaEvent)=>void;update:(config:Config,events:MetaEvent[])=>void};footstepMetaLoading?:Promise<void>};
function Tracking(){const path=usePathname(),params=useSearchParams(),query=params.get('keyword')||params.get('q')||'',visit=useRef({route:'',id:''});const [config,setConfig]=useState<Config|null>(null);
 useEffect(()=>{if(!path)return;if(navigator.doNotTrack==='1'||(navigator as Navigator&{globalPrivacyControl?:boolean}).globalPrivacyControl){setConfig({enabled:false,available:false,privacy_reason:'browser_privacy'});return;}
  const route=path+'?'+query;if(visit.current.route!==route)visit.current={route,id:Array.from(crypto.getRandomValues(new Uint8Array(16)),x=>x.toString(16).padStart(2,'0')).join('')};let stopped=false;
  void fetch('/api/marketing/meta/page?'+new URLSearchParams({path,view:visit.current.id,search:query.slice(0,100)}),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}}).then(async r=>{if(!r.ok)return;const data=await r.json() as {config:Config;events:MetaEvent[]};if(stopped)return;setConfig(data.config);const w=window as MetaWindow;if(!data.config.enabled){w.FootstepMeta?.update(data.config,[]);return;}
   if(w.FootstepMeta){w.FootstepMeta.update(data.config,data.events);return;}w.FootstepMetaConfig=data.config;w.FootstepMetaEvents=data.events;
   if(!w.footstepMetaLoading)w.footstepMetaLoading=new Promise<void>((resolve,reject)=>{const script=document.createElement('script');script.src='/public/assets/js/meta-pixel.js?v=consent-20261111';script.async=true;script.onload=()=>resolve();script.onerror=()=>{w.footstepMetaLoading=undefined;script.remove();reject(new Error('Meta runtime unavailable'));};document.head.appendChild(script);});
   await w.footstepMetaLoading;if(!stopped)(window as MetaWindow).FootstepMeta?.update(data.config,data.events);
  }).catch(()=>{});return()=>{stopped=true;};
 },[path,query]);
 // Keep Meta event handling active for visitors with an existing consent choice,
 // but never interrupt storefront visitors with a marketing-cookie prompt.
 void config;
 return null;}
export default function StorefrontMeta(){return <Suspense fallback={null}><Tracking/></Suspense>;}
