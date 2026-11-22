'use client';
import Link from 'next/link';
import {useEffect,useRef,useState} from 'react';
import Media from './Media';
export type ProductImageBatch={mobile_ids:number[];hour:number;next_change_at:number;items:{id:number;name:string;href:string;image:string}[]};
export default function HourlyProductShowcase({initial}:{initial:ProductImageBatch}){
 const [batch,setBatch]=useState(initial),root=useRef<HTMLDivElement>(null),pending=useRef<ProductImageBatch|null>(null);
 useEffect(()=>{setBatch(initial);},[initial]);
 useEffect(()=>{
  let stopped=false,timer:ReturnType<typeof setTimeout>,timeout:ReturnType<typeof setTimeout>,controller:AbortController|undefined;
  function apply(data:ProductImageBatch){if(root.current?.contains(document.activeElement)){pending.current=data;}else{pending.current=null;setBatch(data);}}
  function schedule(next:number){clearTimeout(timer);timer=setTimeout(refresh,Math.max(1000,Math.min(3600000,next-Date.now()+250)));}
  async function refresh(){controller?.abort();const request=controller=new AbortController();const requestTimeout=timeout=setTimeout(()=>request.abort(),12000);try{const r=await fetch('/api/storefront/v3/home-product-images',{cache:'no-store',signal:request.signal});if(!r.ok)throw new Error('Unavailable');const {data}=await r.json();if(!data||!Array.isArray(data.items)||!Number.isFinite(data.next_change_at)||!data.items.length)throw new Error('Invalid batch');if(!stopped&&controller===request){apply(data);schedule(data.next_change_at);}}catch{if(!stopped&&controller===request)schedule(Date.now()+60000);}finally{clearTimeout(requestTimeout);}}
  function resume(){if(!document.hidden)refresh();}
  function blur(){queueMicrotask(()=>{if(!stopped&&pending.current&&!root.current?.contains(document.activeElement))apply(pending.current);});}
  schedule(batch.next_change_at);document.addEventListener('visibilitychange',resume);document.addEventListener('focusout',blur);
  return()=>{stopped=true;clearTimeout(timer);clearTimeout(timeout);controller?.abort();document.removeEventListener('visibilitychange',resume);document.removeEventListener('focusout',blur);};
 },[batch.next_change_at]);
 return <div ref={root} className="hourly-product-showcase" data-product-image-hour={batch.hour} aria-label="Explore products from different brands">{batch.items.slice(0,6).map((p,i)=><Link key={p.id} href={p.href} prefetch={false} className={`hourly-product-image ${batch.mobile_ids.includes(p.id)?"hourly-mobile-visible":""}`} aria-label={p.name}><Media src={p.image} alt={p.name} eager={i<4}/></Link>)}</div>;
}


