'use client';
import Link from 'next/link';
import {useEffect,useId,useRef,useState} from 'react';
import {usePathname,useRouter} from 'next/navigation';
import type {Collection,Product} from '@/lib/catalog';
import {mediaUrl} from '@/lib/urls.mjs';
const productHref=(id:number,slug?:string)=>`/product/${encodeURIComponent(slug||String(id))}`;

export default function ProductSearch(){
 const [query,setQuery]=useState(''),[open,setOpen]=useState(false),[result,setResult]=useState<Collection<Product>|null>(null),[loading,setLoading]=useState(false),[error,setError]=useState(false),[active,setActive]=useState(-1);
 const cache=useRef(new Map<string,Collection<Product>>()),router=useRouter(),pathname=usePathname(),listId=useId();
 const term=query.trim();
 useEffect(()=>{const sync=()=>setQuery(new URLSearchParams(window.location.search).get('q')||'');sync();window.addEventListener('popstate',sync);return()=>window.removeEventListener('popstate',sync);},[pathname]);
 useEffect(()=>{
  setActive(-1);setError(false);
  if(!open||term.length<2){setLoading(false);setResult(null);return;}
  const saved=cache.current.get(term);if(saved){setResult(saved);setLoading(false);return;}
  const controller=new AbortController();setLoading(true);setResult(null);
  const timer=setTimeout(async()=>{try{const response=await fetch(`/api/catalog-search?q=${encodeURIComponent(term)}`,{signal:controller.signal});if(!response.ok)throw Error();const data=await response.json() as Collection<Product>;if(!controller.signal.aborted){cache.current.set(term,data);if(cache.current.size>30)cache.current.delete(cache.current.keys().next().value!);setResult(data);}}catch{if(!controller.signal.aborted)setError(true);}finally{if(!controller.signal.aborted)setLoading(false);}},400);
  return()=>{clearTimeout(timer);controller.abort();};
 },[term,open]);
 const items=result?.data||[];
 function all(){setOpen(false);router.push(term?`/shop?q=${encodeURIComponent(term)}`:'/shop');}
 function product(index:number){const p=items[index];if(p){setOpen(false);router.push(productHref(p.id,p.slug));}}
 return <form action="/shop" className="w-100 product-search" role="search" onSubmit={e=>{e.preventDefault();all();}} onBlur={e=>{if(!e.currentTarget.contains(e.relatedTarget as Node|null))setOpen(false);}}>
 <div className="input-group"><input data-product-search-input type="search" className="border-0 border-lg form-control" name="q" value={query} onChange={e=>setQuery(e.target.value)} onFocus={()=>setOpen(true)} placeholder="Product, brand বা model খুঁজুন…" aria-label="Search products" maxLength={100} autoComplete="off" role="combobox" aria-autocomplete="list" aria-expanded={open&&term.length>=2} aria-controls={listId} aria-activedescendant={active>=0?`${listId}-${active}`:undefined} onKeyDown={e=>{if(e.key==='Escape'){e.preventDefault();setOpen(false);}else if(e.key==='ArrowDown'&&items.length){e.preventDefault();setOpen(true);setActive(n=>(n+1)%items.length);}else if(e.key==='ArrowUp'&&items.length){e.preventDefault();setOpen(true);setActive(n=>n<=0?items.length-1:n-1);}else if(e.key==='Enter'&&open&&active>=0){e.preventDefault();product(active);}}}/><div className="input-group-append"><button className="btn btn-primary rounded-0" aria-label="Search"><i className="la la-search fs-18"/></button></div></div>
 {open&&term.length>=2&&<div className="product-search-results"><div className="product-search-status" aria-live="polite">{loading?'খোঁজা হচ্ছে…':error?'সাজেশন লোড হয়নি। Search চাপুন।':result?.search?.corrected?`“${result.search.matched_query}” এর কাছাকাছি ফলাফল`:`${result?.meta?.total??0}টি product পাওয়া গেছে`}</div><div id={listId} role="listbox" aria-label="Product suggestions">{items.map((p,i)=><Link role="option" aria-selected={active===i} id={`${listId}-${i}`} href={productHref(p.id,p.slug)} key={p.id} className={`product-search-option ${active===i?'active':''}`} onMouseEnter={()=>setActive(i)} onClick={()=>setOpen(false)}><img src={mediaUrl(p.thumbnail_image,process.env.NEXT_PUBLIC_LARAVEL_ORIGIN||'')} alt="" width="42" height="54"/><span><strong>{p.name}</strong><small>{p.main_price}</small></span><i className="las la-arrow-up" aria-hidden="true"/></Link>)}</div>{!loading&&!error&&!items.length&&<p className="product-search-empty">অন্য নাম, brand বা model দিয়ে খুঁজুন।</p>}<button type="submit" className="product-search-all">সব ফলাফল দেখুন →</button></div>}
 </form>;
}
