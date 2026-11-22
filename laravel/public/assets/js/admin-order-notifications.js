(function(){'use strict';
const root=document.getElementById('ops-notifications');if(!root)return;
const labels={all:'সব',confirmed:'কনফার্ম',delivery:'ডেলিভারি',cancelled:'ক্যানসেল',other:'অন্যান্য'};
const statuses={delivered:'ডেলিভারি সম্পন্ন',on_delivery:'ডেলিভারির পথে',on_the_way:'ডেলিভারির পথে',shipped:'পাঠানো হয়েছে',partial_delivered:'আংশিক ডেলিভারি'};
let selected='all',timer,controller,sequence=0;const list=document.getElementById('ops-notification-items');
const filters=document.createElement('div');filters.className='ops-notification-filters';filters.setAttribute('aria-label','নোটিফিকেশনের ধরন');list.before(filters);
function tabs(counts){filters.replaceChildren();Object.entries(labels).forEach(([key,label])=>{if(key==='other'&&!counts.other)return;const b=document.createElement('button');b.type='button';b.className=key===selected?'active':'';b.setAttribute('aria-pressed',String(key===selected));b.textContent=label+(key!=='all'?' ('+(counts[key]||0)+')':'');b.onclick=()=>{selected=key;refresh();};filters.append(b);});}
async function refresh(){clearTimeout(timer);if(document.hidden)return;if(controller)controller.abort();controller=new AbortController();const id=++sequence;
try{const url=new URL(root.dataset.url,location.origin);if(selected!=='all')url.searchParams.set('category',selected);
const res=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin',signal:controller.signal});if(!res.ok)throw Error();const data=await res.json();if(id!==sequence)return;
const badge=document.getElementById('ops-notification-count');const count=Math.max(0,Number(data.count)||0);badge.textContent=count>99?'99+':String(count);badge.hidden=count===0;
root.querySelector('button').setAttribute('aria-label','Notifications: '+count+' unread');tabs(data.counts||{});list.replaceChildren();
data.items.forEach(item=>{const a=document.createElement('a');a.className='ops-notification-row';a.href=item.url;
const kind=document.createElement('span');kind.className='ops-notification-kind';kind.textContent=statuses[item.status]||item.label||item.status;
const code=document.createElement('span');code.textContent=item.code;
const time=document.createElement('small');time.textContent=(item.at||'')+' · BD';a.append(kind,code,time);list.append(a);});
if(!data.items.length){const empty=document.createElement('div');empty.className='p-3 text-muted';empty.textContent='এই বিভাগে নতুন নোটিফিকেশন নেই।';list.append(empty);}
}catch(e){if(e.name!=='AbortError'&&id===sequence)list.textContent='নোটিফিকেশন লোড হয়নি। আবার চেষ্টা করুন।';}
finally{if(id===sequence)timer=setTimeout(refresh,20000);}}
document.addEventListener('visibilitychange',()=>{clearTimeout(timer);if(!document.hidden)refresh();});refresh();
}());
