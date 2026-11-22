(function(){
 'use strict';
 const root=document.getElementById('admin-order-shortcuts');if(!root)return;
 let timer,controller;
 async function refresh(){
  clearTimeout(timer);if(document.hidden)return;
  if(controller)controller.abort();controller=new AbortController();
  try{
   const response=await fetch(root.dataset.url,{headers:{Accept:'application/json'},cache:'no-store',signal:controller.signal});
   if(!response.ok)throw Error('Request failed');const counts=await response.json();
   for(const badge of root.querySelectorAll('[data-order-menu]')){
    const count=Math.max(0,Number(counts[badge.dataset.orderMenu])||0);
    badge.textContent=count>99?'99+':String(count);badge.hidden=count===0;
   }
  }catch(error){}finally{if(!document.hidden)timer=setTimeout(refresh,20000);}
 }
 document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)refresh();});
 refresh();
})();
