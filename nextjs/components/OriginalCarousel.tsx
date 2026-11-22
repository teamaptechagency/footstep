'use client';
import {ReactNode,useEffect,useRef,useState} from 'react';
export default function OriginalCarousel({children,autoSlide=false,intervalSeconds=2}:{children:ReactNode[];autoSlide?:boolean;intervalSeconds?:number}){
 const root=useRef<HTMLDivElement>(null),[width,setWidth]=useState(0),[visible,setVisible]=useState(6),[offset,setOffset]=useState(0);
 const pausedUntil=useRef(0);
 useEffect(()=>{const el=root.current;if(!el)return;const update=()=>{setWidth(el.clientWidth);setVisible(window.innerWidth>=1500?6:window.innerWidth>=1200?5:window.innerWidth>=992?4:window.innerWidth>=768?4:2);setOffset(0);};update();const observer=new ResizeObserver(update);observer.observe(el);return()=>observer.disconnect();},[]);
 const step=(width+20)/visible;
 useEffect(()=>{
  const el=root.current;if(!el||!autoSlide||children.length<=visible)return;
  const motion=window.matchMedia('(prefers-reduced-motion: reduce)');
  let inView=false;
  const observer=new IntersectionObserver(([entry])=>{inView=entry.isIntersecting;},{threshold:.2});observer.observe(el);
  const pause=()=>{pausedUntil.current=Date.now()+10000;};
  el.addEventListener('pointerdown',pause);el.addEventListener('focusin',pause);
  const timer=window.setInterval(()=>{
   if(motion.matches||document.hidden||!inView||Date.now()<pausedUntil.current||el.contains(document.activeElement)||document.querySelector('.next-checkout-overlay'))return;
   setOffset(current=>current>=children.length-visible?0:Math.min(children.length-visible,current+1));
  },Math.max(2,Math.min(60,intervalSeconds))*1000);
  return()=>{clearInterval(timer);observer.disconnect();el.removeEventListener('pointerdown',pause);el.removeEventListener('focusin',pause);};
 },[autoSlide,intervalSeconds,children.length,visible]);
 return <div className="original-product-carousel" ref={root}><div className="next-carousel-viewport"><div className="next-carousel-track" style={{transform:`translateX(-${offset*step}px)`}}>{children.map((child,i)=><div className="carousel-box" key={i} style={{flex:`0 0 ${width?`${step-20}px`:`calc((100% - ${(visible-1)*20}px) / ${visible})`}`}}>{child}</div>)}</div></div>{children.length>visible&&<><button className="next-carousel-prev" aria-label="Previous products" onClick={()=>setOffset(Math.max(0,offset-visible))}><i className="las la-angle-left"/></button><button className="next-carousel-next" aria-label="Next products" onClick={()=>setOffset(offset+visible>=children.length?0:Math.min(children.length-visible,offset+visible))}><i className="las la-angle-right"/></button></>}</div>;
}
