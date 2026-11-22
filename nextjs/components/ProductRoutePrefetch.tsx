'use client';

import {useEffect} from 'react';
import {usePathname, useRouter} from 'next/navigation';
import {PrefetchKind} from 'next/dist/client/components/router-reducer/router-reducer-types';

type Connection = {saveData?: boolean; effectiveType?: string};

export default function ProductRoutePrefetch() {
 const pathname=usePathname();
 const router=useRouter();

 useEffect(()=>{
  if(pathname!=='/'&&!pathname.startsWith('/product/'))return;
  const main=document.getElementById('main');
  if(!main)return;
  const connection=(navigator as Navigator & {connection?:Connection}).connection;
  if(connection?.saveData)return;
  const slow=/^(slow-2g|2g|3g)$/.test(connection?.effectiveType||'');
  const warmed=new Set<string>();
  let active=true;
  let backgroundTimer:ReturnType<typeof setTimeout>|undefined;
  let intentTimer:ReturnType<typeof setTimeout>|undefined;

  function productHref(anchor:HTMLAnchorElement) {
   const url=new URL(anchor.href);
   if(url.origin!==location.origin||!/^\/product\/[^/]+$/.test(url.pathname)||url.pathname===pathname)return null;
   return url.pathname+url.search;
  }

  function warm(href:string) {
   if(!active||document.hidden||warmed.has(href)||warmed.size>=12)return;
   warmed.add(href);
   router.prefetch(href,{kind:PrefetchKind.FULL,onInvalidate:()=>{if(active)warmed.delete(href);}});
  }

  function homeCandidates() {
   const nearby=[...main!.querySelectorAll<HTMLAnchorElement>('a.product-photo, a.today-deal-item')]
    .map(anchor=>({href:productHref(anchor),box:anchor.getBoundingClientRect()}))
    .filter(item=>item.href&&item.box.width>0&&item.box.height>0&&item.box.right>0&&item.box.left<innerWidth&&item.box.bottom>0)
    .sort((a,b)=>Math.max(0,a.box.top-innerHeight)-Math.max(0,b.box.top-innerHeight));
   return [...new Set(nearby.map(item=>item.href!))];
  }

  function warmHome() {
   if(!active||document.hidden)return;
   const urls=homeCandidates().slice(0,slow?1:4);
   let index=0;
   const next=()=>{
    const href=urls[index++];
    if(href)warm(href);
    if(index<urls.length)backgroundTimer=setTimeout(next,slow?650:330);
   };
   next();
  }

  function onIntent(event:Event) {
   if(!(event.target instanceof Element))return;
   const anchor=event.target.closest<HTMLAnchorElement>('a[href]');
   if(!anchor||anchor.target==='_blank')return;
   const href=productHref(anchor);
   if(!href)return;
   clearTimeout(intentTimer);
   if(event.type==='pointerover')intentTimer=setTimeout(()=>warm(href),90);
   else warm(href);
  }

  function warmAfterLayout() {
   // Hero and product rows may move as their images finish loading.
   const href=homeCandidates()[0];
   if(href)warm(href);
  }

  if(pathname==='/'){
   backgroundTimer=setTimeout(warmHome,slow?450:180);
   if(!slow&&document.readyState!=='complete')window.addEventListener('load',warmAfterLayout,{once:true});
  }
  main.addEventListener('pointerover',onIntent);
  main.addEventListener('pointerdown',onIntent,{passive:true});
  main.addEventListener('focusin',onIntent);
  return ()=>{
   active=false;
   clearTimeout(backgroundTimer);
   clearTimeout(intentTimer);
   window.removeEventListener('load',warmAfterLayout);
   main.removeEventListener('pointerover',onIntent);
   main.removeEventListener('pointerdown',onIntent);
   main.removeEventListener('focusin',onIntent);
  };
 },[pathname,router]);

 return null;
}
