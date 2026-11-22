'use client';
import {useEffect,useRef,useState} from 'react';
import Media from './Media';
export default function Gallery({images,title,discount}:{images:string[];title:string;discount?:{percent:number;upTo:boolean;label:string}|null}) {
 const [active,setActive]=useState(0),[scale,setScale]=useState(1),[pos,setPos]=useState({x:0,y:0});
 const stage=useRef<HTMLDivElement>(null);
 const points=useRef(new Map<number,{x:number;y:number}>()); const pinch=useRef(0);
 const scaleRef=useRef(1);
 useEffect(()=>{scaleRef.current=scale;},[scale]);
 useEffect(()=>{const el=stage.current;if(!el)return;const wheel=(e:WheelEvent)=>{e.preventDefault();setScale(Math.max(1,Math.min(3,scaleRef.current*Math.exp(-e.deltaY*.002))));};el.addEventListener('wheel',wheel,{passive:false});return()=>el.removeEventListener('wheel',wheel);},[]);
 const zoom=(v:number)=>setScale(Math.max(1,Math.min(3,v)));
 function reset(){setScale(1);setPos({x:0,y:0});}
 function clampPos(x:number,y:number){const r=stage.current?.getBoundingClientRect();if(!r)return;const mx=r.width*(scale-1)/2,my=r.height*(scale-1)/2;setPos({x:Math.max(-mx,Math.min(mx,x)),y:Math.max(-my,Math.min(my,y))});}
 return <div className="gallery"><div ref={stage} className={`gallery-stage ${scale>1?'zoomed':''}`} onDoubleClick={()=>scale>1?reset():zoom(2)}
 onPointerDown={e=>{points.current.set(e.pointerId,{x:e.clientX,y:e.clientY});}}
 onPointerMove={e=>{const old=points.current.get(e.pointerId);if(!old)return;if((points.current.size>1||scale>1)&&Math.hypot(e.clientX-old.x,e.clientY-old.y)>3)e.currentTarget.setPointerCapture(e.pointerId);points.current.set(e.pointerId,{x:e.clientX,y:e.clientY});const all=[...points.current.values()];if(all.length===2){const d=Math.hypot(all[0].x-all[1].x,all[0].y-all[1].y);if(pinch.current)zoom(scale*d/pinch.current);pinch.current=d;}else if(scale>1)clampPos(pos.x+e.clientX-old.x,pos.y+e.clientY-old.y);}}
 onPointerUp={e=>{points.current.delete(e.pointerId);pinch.current=0;}} onPointerCancel={e=>{points.current.delete(e.pointerId);pinch.current=0;}}>
 {discount&&<div className={`gallery-sale-badge ${discount.upTo?'has-range':''}`} aria-label={discount.label}><span className="gallery-sale-caption">{discount.upTo?'UP TO':'DISCOUNT'}</span><strong>{discount.percent>0?<>−{discount.percent}%</>:'SPECIAL PRICE'}</strong></div>}
 <div className="gallery-transform" style={{transform:`translate(${pos.x}px,${pos.y}px) scale(${scale})`}}><Media src={images[active]||'/placeholder.svg'} alt={title} eager/></div>
 </div><div className="gallery-thumbs">{images.map((src,i)=><button key={src} className={i===active?'active':''} aria-label={`Photo ${i+1}`} aria-pressed={i===active} onClick={()=>{setActive(i);reset();}}><Media src={src} alt=""/></button>)}</div><p className="gallery-hint">Scroll to zoom · Double-click to reset · Pinch and drag on mobile</p></div>;
}
