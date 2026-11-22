(function(){'use strict';
 const node=(tag,text,cls)=>{const el=document.createElement(tag);if(text!==undefined)el.textContent=String(text);if(cls)el.className=cls;return el;};
 const percent=value=>Number.isFinite(value)?value.toFixed(1)+'%':'তথ্য নেই';
 function render(body,report,cached){
  body.replaceChildren();
  const s=report.summary;
  if(s){
   const card=node('section',undefined,'cs-overall');card.append(node('small','OVERALL DELIVERY HISTORY'));
   const gauge=node('div',undefined,'cs-gauge'),fill=node('div',undefined,'cs-gauge-fill');
   const rate=Number.isFinite(s.rate)?Math.max(0,Math.min(100,s.rate)):0;
   fill.style.background='conic-gradient(from 270deg,#1ba459 0deg '+(rate*1.8)+'deg,#ed5a61 '+(rate*1.8)+'deg 180deg,transparent 180deg)';
   gauge.append(fill,node('strong',s.rate===null?'—':percent(s.rate)),node('span','Delivered'));card.append(gauge);
   card.append(node('h3',percent(s.rate)+' Delivered','cs-delivered'));
   const cancelledRate=s.total>0?100*s.cancelled/s.total:null;
   card.append(node('p',percent(cancelledRate)+' Cancelled','cs-cancelled'),node('p',s.total+' orders · '+s.delivered+' delivered · '+s.cancelled+' cancelled','cs-counts'));
   if(s.total>s.delivered+s.cancelled)card.append(node('p',(s.total-s.delivered-s.cancelled)+' other / pending','cs-counts'));
   body.append(card);
  }else body.append(node('p','Provider overall total দেয়নি। Courier records যোগ করে total অনুমান করা হয়নি।','cs-note'));
  const steadfast=report.couriers.find(c=>c.key==='steadfast'||/steadfast/i.test(c.name));
  const detail=node('section',undefined,'cs-detail-grid');
  const parcel=node('div',undefined,'cs-stat');parcel.append(node('small','STEADFAST PARCEL HISTORY'),node('strong',steadfast?steadfast.total:'তথ্য নেই'));
  if(steadfast)parcel.append(node('p',steadfast.delivered+' delivered · '+steadfast.cancelled+' cancelled · '+percent(steadfast.rate)+' success'));
  detail.append(parcel);
  const risk=report.risk||{},fraud=node('div',undefined,'cs-stat cs-fraud');fraud.append(node('small','FRAUD REPORTS · BD COURIER'),node('strong',risk.reports_available?risk.report_count:'তথ্য পাওয়া যায়নি'));
  fraud.append(node('p','Provider-এর report; Steadfast-এর সরাসরি fraud clearance নয়।'));detail.append(fraud);body.append(detail);
  body.append(node('h3','Courier breakdown','cs-section-title'));
  const wrap=node('div',undefined,'cs-table'),table=node('table'),head=node('thead'),hr=node('tr');
  ['Courier','Total','Delivered','Cancelled','Success'].forEach(v=>hr.append(node('th',v)));head.append(hr);table.append(head);
  const rows=node('tbody');report.couriers.forEach(c=>{const tr=node('tr');[c.name,c.total,c.delivered,c.cancelled,percent(c.rate)].forEach(v=>tr.append(node('td',v)));rows.append(tr);});
  if(s){const tr=node('tr',undefined,'cs-total');['Overall',s.total,s.delivered,s.cancelled,percent(s.rate)].forEach(v=>tr.append(node('td',v)));rows.append(tr);}
  table.append(rows);wrap.append(table);body.append(wrap);
  const verdict=node('section',undefined,'cs-verdict');verdict.append(node('h3','Risk / Fraud check details'));
  if(risk.level){verdict.classList.add('risk-'+risk.level);verdict.append(node('strong',risk.label||risk.level),node('p',risk.action||'Provider action পাওয়া যায়নি।'));const list=node('ul');(risk.reasons||[]).forEach(t=>list.append(node('li',t)));verdict.append(list);}
  else verdict.append(node('p','API response-এ risk verdict পাওয়া যায়নি।'));
  if(risk.reports_available){
   if(risk.report_count===0)verdict.append(node('p','এই API response-এ fraud report নেই। এটি ভবিষ্যৎ delivery-এর নিশ্চয়তা নয়।'));
   (risk.reports||[]).forEach(r=>{const item=node('article',undefined,'cs-fraud-item');Object.entries(r).forEach(([key,value])=>item.append(node('p',key+': '+value)));verdict.append(item);});
   if(risk.report_count>(risk.reports||[]).length)verdict.append(node('p','সব report-এর বিস্তারিত provider response-এ নেই বা তালিকা সীমিত করা হয়েছে।'));
  }else verdict.append(node('p','Fraud report তথ্য পাওয়া যায়নি; শূন্য ধরে দেখানো হয়নি।'));
  verdict.append(node('p','Success rate = delivered ÷ total × 100। Steadfast direct fraud data এই connection-এ verified নয়।','cs-note'));body.append(verdict);
  body.append(node('p',report.source+' · '+report.checked_at+(cached?' · Cached (15 min)':''),'cs-meta'));
 }
 document.querySelectorAll('[data-courier-success]').forEach(root=>{
  const dialog=root.querySelector('dialog'),body=root.querySelector('.cs-body'),retry=root.querySelector('.cs-retry');let busy=false;
  async function load(){if(busy)return;busy=true;retry.disabled=true;body.replaceChildren(node('p','Delivery history ও risk report যাচাই হচ্ছে…','cs-loading'));
   const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),18000);
   try{const res=await fetch(root.dataset.url,{method:'POST',credentials:'same-origin',signal:controller.signal,headers:{Accept:'application/json','X-CSRF-TOKEN':root.dataset.csrf}});const data=await res.json().catch(()=>null);
    if(!res.ok||!data)throw Error(res.status===403?'শুধু admin এই report দেখতে পারবেন।':res.status===429?'অনেকবার check হয়েছে। পরে চেষ্টা করুন।':'Report load হয়নি। আবার চেষ্টা করুন।');
    if(!data.ok)throw Error(data.message||'Report unavailable');render(body,data.report,data.cached);
   }catch(e){body.replaceChildren(node('p',e.name==='AbortError'?'Provider response দেরি করছে। আবার চেষ্টা করুন।':e.message,'cs-error'));}
   finally{clearTimeout(timer);busy=false;retry.disabled=false;}
  }
  root.querySelector('.cs-trigger').onclick=()=>{dialog.showModal();load();};root.querySelector('.cs-close').onclick=()=>dialog.close();
  dialog.addEventListener('click',e=>{if(e.target===dialog){const r=dialog.getBoundingClientRect();if(e.clientX<r.left||e.clientX>r.right||e.clientY<r.top||e.clientY>r.bottom)dialog.close();}});retry.onclick=load;
 });
}());
