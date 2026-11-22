'use client';
import Link from 'next/link';
import {useEffect,useState} from 'react';
import {usePathname} from 'next/navigation';
import ProductSearch from './ProductSearch';
export default function Header({logo,menu,backend}:{logo:string;menu:{label:string;href:string}[];backend:string}) {
 const [search,setSearch]=useState(false);const pathname=usePathname();const compact=pathname==='/flash-deal/adidas-terrex-3y3qt';const [clock,setClock]=useState('24 : 00 : 00');
 useEffect(()=>{if(search)document.querySelector<HTMLInputElement>('[data-product-search-input]')?.focus();},[search]);
 useEffect(()=>{if(!compact)return;let start=Date.now();try{const key='footstep:flash:first-visit:v1:'+pathname;const saved=Number(localStorage.getItem(key));if(saved>0&&saved<=start)start=saved;else localStorage.setItem(key,String(start));}catch{}const tick=()=>{const seconds=Math.max(0,Math.floor((start+86400000-Date.now())/1000));setClock([Math.floor(seconds/3600),Math.floor(seconds%3600/60),seconds%60].map(n=>String(n).padStart(2,'0')).join(' : '));};tick();const timer=setInterval(tick,1000);return()=>clearInterval(timer);},[compact,pathname]);
 if(/^\/flash-deal\/[^/]+\/?$/.test(pathname))return null;
 return <><header className={`z-1020 bg-white border-bottom shadow-sm original-header ${compact?'next-hide-compact':''}`}>
  <div className="position-relative logo-bar-area z-1"><div className="container"><div className="d-flex align-items-center">
   <div className="col-auto col-xl-3 pl-0 pr-3 d-flex align-items-center"><Link className="d-block py-20px mr-3 ml-0" href="/"><img src={logo} alt="Footstep online" className="mw-100 h-40px h-md-60px" height="40"/></Link>{pathname!=='/'&&<Link href="/categories" className="d-none d-xl-flex align-items-center ml-auto btn btn-light border" aria-label="Categories"><i className="las la-bars"/></Link>}</div>
   <button className="d-lg-none ml-auto btn p-2 text-reset" aria-label="Search" onClick={()=>setSearch(!search)}><i className="las la-search la-2x"/></button>
   <div className={`flex-grow-1 front-header-search align-items-center bg-white ${search?'next-search-open':''}`}><button className="btn d-lg-none px-2" type="button" aria-label="Close search" onClick={()=>setSearch(false)}><i className="la la-long-arrow-left la-2x"/></button><ProductSearch/></div>
   <div className="d-none d-lg-flex align-items-center ml-3 header-icons"><a href={`${backend}/cart`}><i className="las la-shopping-cart"/><span>Cart</span></a></div>
  </div></div></div>
  <div className="footstep-header-menu border-top border-gray-200 py-1"><div className="container"><ul className="list-inline mb-0 pl-0 text-center">{[...menu.filter(item=>!item.href.endsWith('/size-guide')),{label:'Size Guide',href:'/size-guide'}].map(item=><li key={item.label} className="list-inline-item mr-0"><Link href={item.href} className="fs-14 px-3 py-2 d-inline-block fw-600 text-reset">{item.label}</Link></li>)}</ul></div></div>
 </header>{compact&&<header className="next-compact-flash"><Link href="/"><img src={logo} alt="Footstep online"/></Link><div><strong>For Today</strong><span role="timer">{clock}</span></div></header>}</>;
}
