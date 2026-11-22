'use client';
import {useEffect} from 'react';
import {usePathname} from 'next/navigation';
export default function StorefrontAnalytics(){const path=usePathname();useEffect(()=>{
 if(!path||navigator.doNotTrack==='1'||(navigator as Navigator & {globalPrivacyControl?:boolean}).globalPrivacyControl)return;
 let stopped=false,csrf='',payload='',seconds=0,last=Date.now(),view='';let timer:ReturnType<typeof setInterval>|undefined;
 const post=(kind:'view'|'pulse',keepalive=false)=>fetch('/api/analytics/collect',{method:'POST',credentials:'same-origin',keepalive,headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':csrf},body:JSON.stringify({kind,view,...(kind==='view'?{payload,referrer:document.referrer}:{active_seconds:Math.floor(seconds)})})}).catch(()=>{});
 const tick=()=>{const now=Date.now();if(document.visibilityState==='visible'&&document.hasFocus())seconds+=Math.min(30,(now-last)/1000);last=now;};
 const flush=()=>{tick();if(csrf&&view)void post('pulse',true);};
 void fetch('/api/analytics/page?path='+encodeURIComponent(path),{credentials:'same-origin',cache:'no-store',headers:{Accept:'application/json'}}).then(async r=>{if(!r.ok||r.status===204)return;const data=await r.json();if(stopped)return;csrf=data.csrf;payload=data.payload;view=Array.from(crypto.getRandomValues(new Uint8Array(16)),n=>n.toString(16).padStart(2,'0')).join('');await post('view');if(stopped)return;last=Date.now();timer=setInterval(()=>{tick();void post('pulse');},15000);}).catch(()=>{});
 document.addEventListener('visibilitychange',flush);window.addEventListener('pagehide',flush);
 return ()=>{stopped=true;if(timer)clearInterval(timer);flush();document.removeEventListener('visibilitychange',flush);window.removeEventListener('pagehide',flush);};
 },[path]);return null;}
