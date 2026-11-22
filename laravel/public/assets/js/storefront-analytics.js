(function(){'use strict';
function start(){
 const boot=document.getElementById('storefront-analytics');if(!boot||navigator.doNotTrack==='1'||navigator.globalPrivacyControl===true||!window.crypto?.getRandomValues)return;
 const bytes=new Uint8Array(16);crypto.getRandomValues(bytes);const view=Array.from(bytes,v=>v.toString(16).padStart(2,'0')).join('');
 // Preserve the original inbound source across internal page navigation.
 const attributionKey='footstep.analytics.source.v1';let inbound='';
 try{const ref=document.referrer?new URL(document.referrer):null;if(ref&&ref.hostname!==location.hostname)inbound=ref.origin;}catch(e){}
 const taggedSource=new URLSearchParams(location.search).get('utm_source')?.toLowerCase();
 const knownSources={facebook:'https://facebook.com',fb:'https://facebook.com',instagram:'https://instagram.com',ig:'https://instagram.com',google:'https://google.com',tiktok:'https://tiktok.com',bing:'https://bing.com',youtube:'https://youtube.com'};
 if(taggedSource&&knownSources[taggedSource])inbound=knownSources[taggedSource];
 let source=inbound;
 try{const previous=JSON.parse(sessionStorage.getItem(attributionKey)||'null');if(!source&&previous&&Date.now()-previous.seen<1800000)source=previous.source||'';}catch(e){}
 let sourceSavedAt=0;
 function rememberSource(){if(Date.now()-sourceSavedAt<15000)return;sourceSavedAt=Date.now();try{sessionStorage.setItem(attributionKey,JSON.stringify({source,seen:Date.now()}));}catch(e){}}
 rememberSource();
 let ready=false,active=0,last=performance.now(),interaction=Date.now();
 function tick(){const now=performance.now();if(document.visibilityState==='visible'&&document.hasFocus()&&Date.now()-interaction<60000){active+=Math.max(0,Math.min(now-last,5000));rememberSource();}last=now;}
 ['pointerdown','keydown','touchstart','scroll'].forEach(event=>window.addEventListener(event,()=>{interaction=Date.now();},{passive:true}));
 function body(kind){const data=new FormData();data.set('_token',boot.dataset.csrf);data.set('kind',kind);data.set('view',view);if(kind==='view'){data.set('payload',boot.dataset.payload);data.set('referrer',source);}else data.set('active_seconds',Math.floor(active/1000));return data;}
 function pulse(final){tick();if(!ready)return;const data=body('pulse');if(final&&navigator.sendBeacon){navigator.sendBeacon(boot.dataset.endpoint,data);return;}fetch(boot.dataset.endpoint,{method:'POST',body:data,credentials:'same-origin',keepalive:true,headers:{'Accept':'application/json'}}).catch(()=>{});}
 fetch(boot.dataset.endpoint,{method:'POST',body:body('view'),credentials:'same-origin',headers:{'Accept':'application/json'}}).then(response=>{ready=response.status===200;}).catch(()=>{});
 setInterval(tick,1000);setInterval(()=>pulse(false),15000);
 document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='hidden')pulse(true);else{last=performance.now();interaction=Date.now();}});
 window.addEventListener('pagehide',()=>pulse(true));
}
function schedule(){if(window.requestIdleCallback)requestIdleCallback(start,{timeout:2500});else setTimeout(start,1500);}
if(document.readyState==='complete')schedule();else window.addEventListener('load',schedule,{once:true});
})();
