(function(){
 'use strict';
 const dialog=document.getElementById('admin-search-dialog');if(!dialog)return;
 const input=document.getElementById('admin-search-query'),form=document.getElementById('admin-search-form'),list=document.getElementById('admin-search-results'),state=document.getElementById('admin-search-state'),filters=document.getElementById('admin-search-filters');
 const names={menus:'Menus / মেনু',products:'Products / পণ্য',orders:'Orders / অর্ডার',customers:'Customers / কাস্টমার',brands:'Brands',categories:'Categories',deals:'Flash deals',media:'Media / ফাইল'};
 let pending,timer,serial=0,items=[],selected='all';
 function safeUrl(value){try{const url=new URL(value,location.origin);return url.origin===location.origin&&/^https?:$/.test(url.protocol)?url.href:null;}catch(e){return null;}}
 function render(){
  list.replaceChildren();filters.replaceChildren();
  const counts={};items.forEach(item=>{counts[item.group]=(counts[item.group]||0)+1;});
  if(selected!=='all'&&!counts[selected])selected='all';
  if(items.length){for(const key of ['all',...Object.keys(names).filter(key=>counts[key])]){const button=document.createElement('button');button.type='button';button.textContent=(key==='all'?'All':names[key])+' ('+(key==='all'?items.length:counts[key])+')';button.setAttribute('aria-pressed',String(selected===key));button.onclick=()=>{selected=key;render();};filters.append(button);}}
  for(const key of Object.keys(names)){
   const group=items.filter(item=>item.group===key&&(selected==='all'||selected===key));if(!group.length)continue;
   const h=document.createElement('h2');h.textContent=names[key];list.append(h);
   group.forEach(item=>{const href=safeUrl(item.url);if(!href)return;const a=document.createElement('a'),strong=document.createElement('strong'),small=document.createElement('small');a.href=href;strong.textContent=item.title;small.textContent=[item.type,item.subtitle].filter(Boolean).join(' · ');a.append(strong,small);list.append(a);});
  }
 }
 function clear(){items=[];selected='all';render();}
 document.getElementById('admin-search-open').onclick=()=>{if(!dialog.open)dialog.showModal();input.focus();};
 document.getElementById('admin-search-close').onclick=()=>dialog.close();
 dialog.addEventListener('click',e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();}});
 dialog.addEventListener('close',()=>{serial++;if(pending)pending.abort();clearTimeout(timer);});
 async function search(){
  const q=input.value.trim(),id=++serial;if(pending)pending.abort();clear();
  if([...q].length<2&&!/^[0-9০-৯]+$/.test(q)){state.textContent='কমপক্ষে ২ অক্ষর অথবা একটি ID লিখুন।';return;}
  pending=new AbortController();state.textContent='Searching / খোঁজা হচ্ছে…';
  try{
   const url=new URL(dialog.dataset.url,location.origin);url.searchParams.set('q',q);
   const res=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin',cache:'no-store',signal:pending.signal});
   if(id!==serial)return;
   if(res.status===429){state.textContent='কিছুক্ষণ অপেক্ষা করে আবার Search করুন।';return;}
   if(res.status===401||res.status===403||res.redirected){state.textContent='Session বা permission পরীক্ষা করুন; প্রয়োজনে আবার login করুন।';return;}
   if(res.status===422){state.textContent='সঠিক নাম, নম্বর অথবা ID লিখুন (সর্বোচ্চ ১০০ অক্ষর)।';return;}
   if(!res.ok)throw Error('search_failed');
   const data=await res.json();if(id!==serial)return;
   items=Array.isArray(data.items)?data.items.filter(item=>names[item.group]&&safeUrl(item.url)):[];render();
   state.textContent=items.length?items.length+'টি ফলাফল · প্রতিটি বিভাগে সর্বশেষ মিলগুলো দেখানো হচ্ছে। আরও নির্দিষ্ট লিখে খুঁজতে পারেন।':'কোনো ফল পাওয়া যায়নি। নামের অংশ, মোবাইল বা অর্ডার নম্বর দিয়ে আবার খুঁজুন।';
  }catch(e){if(e.name!=='AbortError'&&id===serial)state.textContent='Search করা যায়নি। Connection পরীক্ষা করে আবার চেষ্টা করুন।';}
 }
 form.onsubmit=e=>{e.preventDefault();clearTimeout(timer);search();};
 input.oninput=()=>{serial++;if(pending)pending.abort();clearTimeout(timer);clear();state.textContent=input.value.trim()?'Searching / খোঁজা হচ্ছে…':'কমপক্ষে ২ অক্ষর অথবা একটি ID লিখুন।';timer=setTimeout(search,350);};
}());
