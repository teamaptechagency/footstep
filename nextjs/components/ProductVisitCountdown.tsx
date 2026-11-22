'use client';
import {useEffect,useState} from 'react';

// Each product gets one deadline in this browser; expiry never starts a new cycle.
export default function ProductVisitCountdown({productId}:{productId:number}){
 const [remaining,setRemaining]=useState<number|null>(null);
 useEffect(()=>{
  const key=`footstep:product-visit:v1:${productId}`;
  const now=Date.now();
  let deadline=now+24*60*60*1000;
  try{
   const saved=localStorage.getItem(key);
   const parsed=saved===null?NaN:Number(saved);
   if(Number.isFinite(parsed)&&parsed>0)deadline=parsed;
   else localStorage.setItem(key,String(deadline));
  }catch{/* Storage may be blocked; the timer still works for this page visit. */}
  const tick=()=>setRemaining(Math.max(0,Math.ceil((deadline-Date.now())/1000)));
  tick();
  const interval=window.setInterval(tick,1000);
  document.addEventListener('visibilitychange',tick);
  return()=>{window.clearInterval(interval);document.removeEventListener('visibilitychange',tick)};
 },[productId]);
 const value=remaining===null?'-- : -- : --':[
  Math.floor(remaining/3600),Math.floor(remaining%3600/60),remaining%60,
 ].map(n=>String(n).padStart(2,'0')).join(' : ');
 return <span className="product-visit-countdown" data-product-countdown={productId}>
  <span className="countdown-caption"><i className="las la-clock" aria-hidden="true"/> ২৪ ঘণ্টার কাউন্টডাউন</span>
  <span className="countdown-value" aria-label="Remaining hours, minutes and seconds">{value}</span>
  <style jsx>{`
   .product-visit-countdown{display:inline-flex;flex-direction:column;gap:3px;padding:7px 12px;border:1px solid #e5d7a6;border-radius:8px;background:#fffbef;color:#263c32;flex-shrink:0}
   .countdown-caption{font-size:11px;line-height:1.4;color:#716138}
   .countdown-value{font-size:20px;font-weight:750;line-height:1.25;letter-spacing:1px;font-variant-numeric:tabular-nums;white-space:nowrap}
   @media(max-width:480px){.product-visit-countdown{padding:6px 9px}.countdown-value{font-size:18px}}
  `}</style>
 </span>;
}
