'use client';
import {useEffect,useRef,useState} from 'react';
import type {SizeGuideData} from '@/lib/catalog';
import {mediaUrl} from '@/lib/urls.mjs';
import SizeGuideContent from './SizeGuideContent';
import OrderButton from './OrderButton';

export default function SizeGuidePopup({productId,name,image,price,variant,quantity,available}:{productId:number;name:string;image:string;price:string;variant:string;quantity:number;available:boolean}){
 const [open,setOpen]=useState(false),[guide,setGuide]=useState<SizeGuideData|null>(null),[error,setError]=useState(''),[retry,setRetry]=useState(0);
 const trigger=useRef<HTMLButtonElement>(null),dialog=useRef<HTMLDivElement>(null);
 useEffect(()=>{
  if(!open)return;
  const previous=document.body.style.overflow;document.body.style.overflow='hidden';
  dialog.current?.querySelector<HTMLButtonElement>('button')?.focus();
  const keyboard=(e:KeyboardEvent)=>{
   if(document.querySelector('.next-checkout-dialog'))return;
   if(e.key==='Escape'){e.preventDefault();e.stopImmediatePropagation();setOpen(false)}
   if(e.key==='Tab'){
    const controls=Array.from(dialog.current?.querySelectorAll<HTMLElement>('button:not(:disabled),a[href],iframe,[tabindex="0"]')||[]).filter(el=>el.getClientRects().length);
    const first=controls[0],last=controls[controls.length-1];
    if(e.shiftKey&&document.activeElement===first){e.preventDefault();last?.focus()}
    else if(!e.shiftKey&&document.activeElement===last){e.preventDefault();first?.focus()}
   }
  };
  document.addEventListener('keydown',keyboard,true);
  return()=>{document.body.style.overflow=previous;document.removeEventListener('keydown',keyboard,true);trigger.current?.focus()};
 },[open]);
 useEffect(()=>{
  if(!open||guide)return;
  const controller=new AbortController();setError('');
  fetch('/api/storefront/v3/size-guide',{signal:controller.signal,headers:{Accept:'application/json'}})
   .then(r=>{if(!r.ok)throw Error();return r.json()})
   .then(body=>{if(!body.data?.measurements)throw Error();setGuide(body.data)})
   .catch(e=>{if(e.name!=='AbortError')setError('Size Guide লোড হয়নি। আবার চেষ্টা করুন।')});
  return()=>controller.abort();
 },[open,guide,retry]);
 return <>
  <button ref={trigger} type="button" className="product-size-guide-link" onClick={()=>setOpen(true)}>Size Guide ↗</button>
  {open&&<div className="guide-popup-overlay" onClick={e=>{if(e.target===e.currentTarget)setOpen(false)}}>
   <div ref={dialog} className="guide-popup" role="dialog" aria-modal="true" aria-label="Size Guide">
    <header><strong>Size Guide / সাইজ গাইড</strong><button type="button" onClick={()=>setOpen(false)} aria-label="Close size guide">×</button></header>
    <div className="guide-popup-body">{error?<div role="alert"><p>{error}</p><button type="button" onClick={()=>setRetry(n=>n+1)}>আবার চেষ্টা করুন</button></div>:guide?<SizeGuideContent guide={guide} imageUrl={mediaUrl(guide.image_url,window.location.origin)}/>:<p role="status">Size Guide লোড হচ্ছে…</p>}</div>
    <footer><button className="guide-popup-back" type="button" onClick={()=>setOpen(false)}>← পণ্যে ফিরুন</button><div className="guide-popup-product"><img src={image} alt="" width="38" height="48"/><span><strong>{name}</strong><small>{price} · Size: {variant||'Standard'}</small></span></div><OrderButton id={productId} variant={variant} quantity={quantity} disabled={!available} className="guide-popup-order">Order Now</OrderButton></footer>
   </div>
  </div>}
  <style jsx>{`
   .guide-popup-overlay{position:fixed;inset:0;z-index:2100;background:#142b25a6;display:flex;align-items:center;justify-content:center;padding:18px}
   .guide-popup{width:100%;max-width:980px;max-height:92dvh;background:#f7faf8;border-radius:16px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 80px #0003;text-align:left;color:#163e31}
   header{display:flex;align-items:center;justify-content:space-between;padding:12px 20px;background:#fff;border-bottom:1px solid #dfe9e3;flex-shrink:0}
   header strong{font-size:17px}header button{width:40px;height:40px;border-radius:50%;border:1px solid #cddbd4;background:#f0f5f2;color:#183e32;font-size:30px;font-weight:800;line-height:1}
   .guide-popup-body{overflow:auto;overscroll-behavior:contain;padding:20px;min-height:130px}
   footer{display:flex;align-items:center;gap:12px;padding:12px 18px;background:#fff;border-top:1px solid #dfe9e3;flex-shrink:0}
   .guide-popup-back{border:1px solid #d5e4db;border-radius:7px;background:#f4f8f5;padding:10px;color:#21513d;font-weight:700;white-space:nowrap}
   .guide-popup-product{display:flex;align-items:center;gap:8px;flex:1;min-width:0}.guide-popup-product img{object-fit:cover;border-radius:5px}
   .guide-popup-product strong{display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;font-size:12px}.guide-popup-product small{font-size:11px;color:#087f56}
   footer :global(.guide-popup-order){border:0;border-radius:7px;background:#087f56;color:#fff;padding:12px 16px;font-weight:750;white-space:nowrap}footer :global(.guide-popup-order:disabled){opacity:.5}
   @media(max-width:600px){.guide-popup-overlay{padding:8px}.guide-popup{max-height:96dvh;border-radius:12px}header{padding:8px 12px}header strong{font-size:14px}.guide-popup-body{padding:12px}footer{padding:10px;gap:8px;flex-wrap:wrap}.guide-popup-back{font-size:12px}.guide-popup-product{flex-basis:calc(100% - 125px)}footer :global(.guide-popup-order){width:100%}}
  `}</style>
 </>;
}
