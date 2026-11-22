'use client';

import {useCallback, useEffect, useRef, useState} from 'react';
import {createPortal} from 'react-dom';

let standby: { owner: symbol; href: string; discard: () => boolean; show: (opener: HTMLAnchorElement|null) => void } | undefined;

type Props = {
 id: number; variant?: string; quantity?: number; addonIds?: number[];
 disabled?: boolean; className?: string; children: React.ReactNode;
};

export default function OrderButton({id, variant, quantity=1, addonIds=[], disabled=false, className='', children}: Props) {
 const [open,setOpen]=useState(false);
 const [prepared,setPrepared]=useState(false);
 const [loaded,setLoaded]=useState(false);
 const [failed,setFailed]=useState(false);
 const [attempt,setAttempt]=useState(0);
 const frame=useRef<HTMLIFrameElement>(null);
 const trigger=useRef<HTMLAnchorElement>(null);
 const owner=useRef(Symbol('checkout'));
 const opener=useRef<HTMLAnchorElement|null>(null);
 const showRef=useRef<(button: HTMLAnchorElement|null) => void>(()=>{});
 const opened=useRef(false);
 const restoringFocus=useRef(false);
 const ready=useRef(false);
 const openingSent=useRef(false);
 const preparedAt=useRef(0);
 const intentTimer=useRef<ReturnType<typeof setTimeout> | undefined>(undefined);
 const params=new URLSearchParams({quantity:String(quantity)});
 if(variant)params.set('variant',variant);
 addonIds.forEach(value=>params.append('addon_ids[]',String(value)));
 const checkoutHref=`/commerce/checkout/quick/${id}?${params}`;

 const reset=useCallback(()=>{
  ready.current=false;
  openingSent.current=false;
  preparedAt.current=0;
  setLoaded(false);
  setFailed(false);
 },[]);
 const discard=useCallback(()=>{
  if(opened.current)return false;
  reset();
  setPrepared(false);
  return true;
 },[reset]);
 const prepare=useCallback((replace:boolean)=>{
  if(disabled)return false;
  if(standby&&standby.owner!==owner.current){
   if(standby.href===checkoutHref)return true;
   if(!replace||!standby.discard())return false;
  }
  standby={owner:owner.current,href:checkoutHref,discard,show:button=>showRef.current(button)};
  setPrepared(true);
  return true;
 },[checkoutHref,disabled,discard]);
 const activate=useCallback(()=>{
  if(!opened.current||!ready.current||openingSent.current||!frame.current?.contentWindow)return;
  openingSent.current=true;
  frame.current.contentWindow.postMessage({type:'footstep-checkout-activate'},location.origin);
 },[]);
 const finish=useCallback(()=>{
  opened.current=false;
  if(standby?.owner===owner.current)standby=undefined;
  setOpen(false);
  discard();
  restoringFocus.current=true;
  (opener.current??trigger.current)?.focus();
  opener.current=null;
  restoringFocus.current=false;
 },[discard]);
 const requestClose=useCallback(()=>{
  if(openingSent.current&&frame.current?.contentWindow){
   frame.current.contentWindow.postMessage({type:'footstep-checkout-request-close'},location.origin);
  }else finish();
 },[finish]);
 const retry=useCallback(()=>{
  reset();
  setAttempt(value=>value+1);
 },[reset]);
 const show=useCallback((button:HTMLAnchorElement|null)=>{
  opener.current=button;
  if(failed||(preparedAt.current&&Date.now()-preparedAt.current>90000))retry();
  opened.current=true;
  setOpen(true);
  activate();
 },[activate,failed,retry]);
 useEffect(()=>{showRef.current=show;},[show]);
 const openCheckout=()=>{
  if(standby?.href===checkoutHref){
   standby.show(trigger.current);
   return;
  }
  if(prepare(true))show(trigger.current);
 };

 useEffect(()=>{
  const own=owner.current;
  return ()=>{
   clearTimeout(intentTimer.current);
   if(standby?.owner===own)standby=undefined;
  };
 },[]);
 useEffect(()=>{
  reset();
  if(standby?.owner===owner.current)standby.href=checkoutHref;
 },[checkoutHref,reset]);
 useEffect(()=>{
  const button=trigger.current;
  if(disabled||!button||!('IntersectionObserver' in window))return;
  let timer: ReturnType<typeof setTimeout> | undefined;
  const observer=new IntersectionObserver(([item])=>{
   clearTimeout(timer);
   if(item.isIntersecting)timer=setTimeout(()=>{if(prepare(false))observer.disconnect();},250);
  },{threshold:0.4});
  observer.observe(button);
  return ()=>{clearTimeout(timer);observer.disconnect();};
 },[disabled,prepare]);
 useEffect(()=>{
  const message=(event:MessageEvent)=>{
   if(event.origin!==location.origin||event.source!==frame.current?.contentWindow)return;
   if(event.data?.type==='footstep-checkout-prepared'){
    ready.current=true;
    preparedAt.current=Date.now();
    activate();
   }
   if(event.data?.type==='footstep-checkout-ready'&&opened.current){
    setLoaded(true);
    setFailed(false);
   }
   if(event.data?.type==='footstep-checkout-close')finish();
  };
  window.addEventListener('message',message);
  return ()=>window.removeEventListener('message',message);
 },[activate,finish]);
 useEffect(()=>{
  if(!open)return;
  const overflow=document.body.style.overflow;
  document.body.style.overflow='hidden';
  const close=(event:KeyboardEvent)=>{if(event.key==='Escape'){event.preventDefault();requestClose();}};
  window.addEventListener('keydown',close);
  return ()=>{document.body.style.overflow=overflow;window.removeEventListener('keydown',close);};
 },[open,requestClose]);
 useEffect(()=>{
  if(!open||loaded)return;
  const timeout=setTimeout(()=>setFailed(true),15000);
  return ()=>clearTimeout(timeout);
 },[open,loaded,attempt]);

 const control=disabled
  ? <button type="button" disabled className={className}>{children}</button>
  : <a ref={trigger} href={checkoutHref} role="button" className={className}
    onPointerEnter={()=>{intentTimer.current=setTimeout(()=>prepare(true),180);}}
    onPointerLeave={()=>clearTimeout(intentTimer.current)}
    onFocus={()=>{if(!restoringFocus.current)prepare(false);}}
    onKeyDown={event=>{if(event.key===' '){event.preventDefault();openCheckout();}}}
    onClick={event=>{
     if(event.button!==0||event.metaKey||event.ctrlKey||event.shiftKey||event.altKey)return;
     event.preventDefault();openCheckout();
    }}>{children}</a>;
 return <>{control}{prepared&&createPortal(
  <div className="next-checkout-overlay" role="presentation" style={open?undefined:{display:'none'}}
    onClick={event=>{if(event.target===event.currentTarget)requestClose();}}>
   <div className="next-checkout-dialog" role="dialog" aria-modal={open||undefined}
     aria-label="ক্যাশ অন ডেলিভারিতে অর্ডার করুন" aria-busy={!loaded&&!failed}>
    {open&&!loaded&&<div className="next-checkout-loading" aria-live="polite">{failed
     ? <div role="alert" style={{padding:24,textAlign:'center'}}><strong>অর্ডার ফর্ম লোড করা যায়নি।</strong>
       <p>সংযোগ পরীক্ষা করে আবার চেষ্টা করুন।</p><button type="button" className="btn btn-primary" onClick={retry}>আবার চেষ্টা করুন</button></div>
     : 'অর্ডার ফর্ম লোড হচ্ছে…'}</div>}
    <iframe key={`${checkoutHref}-${attempt}`} ref={frame} title="Cash on delivery checkout"
      src={`${checkoutHref}&prepare=1`} style={{opacity:loaded?1:0}}/>
    {open&&!loaded&&<button type="button" autoFocus className="next-checkout-close" aria-label="Close checkout" onClick={requestClose}>×</button>}
   </div>
  </div>,document.body)}</>;
}
