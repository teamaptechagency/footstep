(() => {
 'use strict';const root=document.getElementById('image-storage');if(!root)return;
 const el=id=>document.getElementById(id),base=root.dataset.base,token=root.dataset.token;
 const mb=n=>(Number(n||0)/1048576).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})+' MB';
 const make=(tag,text)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;return n;};
 let rows=new Map(),busy=false,stop=false,cursor=0,scanId='',loadedDone=false,kind='optimized',days=30,selection=[];
 const wait=ms=>new Promise(resolve=>setTimeout(resolve,ms));
 async function api(path,body){const abort=new AbortController(),timer=setTimeout(()=>abort.abort(),60000);try{
  const r=await fetch(base+path,{method:body?'POST':'GET',credentials:'same-origin',cache:'no-store',redirect:'error',headers:{Accept:'application/json','X-CSRF-TOKEN':token},body,signal:abort.signal});let j;try{j=await r.json();}catch(e){throw new Error('Server response পড়া যায়নি। Session, hosting quota ও error log দেখুন।');}
  if(!r.ok){const e=new Error((j.message||'Request failed')+' '+Object.values(j.errors||{}).flat().join(' ')+' · HTTP '+r.status);e.status=r.status;e.retry=Math.max(5,Math.min(3600,Number(r.headers.get('Retry-After'))||60));throw e;}return j;
 }catch(e){if(e.name==='AbortError')throw new Error('Request timeout. কোনো delete আবার পাঠানোর আগে তালিকা reload করুন—আগের request হয়ে থাকতে পারে।');throw e;}finally{clearTimeout(timer);}}
 const form=data=>{const f=new FormData();Object.entries(data).forEach(([k,v])=>f.set(k,String(v)));return f;};
 function selected(){return Array.from(root.querySelectorAll('[data-image-select]:checked')).map(n=>n.value);}
 function controls(){const ids=selected();root.querySelectorAll('[data-image-select]').forEach(n=>n.disabled=busy||rows.get(n.value)?.selectable===false);el('is-selection').textContent=ids.length+' selected · '+mb(ids.reduce((sum,id)=>sum+(rows.get(id)?.bytes||0),0));const eligible=[...rows.values()].filter(r=>r.selectable!==false).length;el('is-all').checked=eligible>0&&ids.length===eligible;el('is-all').indeterminate=ids.length>0&&ids.length<eligible;el('is-delete').disabled=busy||!ids.length;el('is-load').disabled=busy;el('is-kind').disabled=busy;el('is-days').disabled=busy;el('is-stop').disabled=!busy;el('is-all').disabled=busy;el('is-submit').disabled=busy;el('is-count').textContent=rows.size+' images loaded';el('is-bytes').textContent=mb([...rows.values()].reduce((sum,r)=>sum+r.bytes,0));}
 function add(data){data.forEach(r=>{const key=kind==='version'?'v:'+r.id:'o:'+r.name;if(rows.has(key))return;rows.set(key,r);const tr=make('tr');tr.dataset.imageRow=key;
  const td=make('td'),check=make('input');check.type='checkbox';check.value=key;check.disabled=r.selectable===false;check.dataset.imageSelect='';check.setAttribute('aria-label','Select '+r.name);td.append(check);
  const image=make('td'),img=make('img');img.src=r.url;img.alt=r.name||'Image';img.loading='lazy';image.append(img);
  const name=make('td',r.name);if(r.product)name.append(make('small',r.product));if(r.label)name.append(make('small',r.label));
  const size=make('td',mb(r.bytes));size.append(make('small',kind==='version'?r.age_days+' days':new Date(r.mtime*1000).toLocaleDateString()));
  const status=make('td',r.reason||'Obsolete key; references rechecked before deletion.');status.dataset.imageStatus='';tr.append(td,image,name,size,status);el('is-rows').append(tr);
 });controls();}
 function reset(){rows=new Map();cursor=0;scanId='';loadedDone=false;el('is-rows').replaceChildren();el('is-confirm').hidden=true;el('is-delete-form').reset();kind=el('is-kind').value;days=Number(el('is-days').value);el('is-explanation').textContent=kind==='optimized'?'Unused optimized copies: current source keys ও metadata-এর সঙ্গে মিলিয়ে পুরোনো generated files খুঁজবে। Original মুছবে না।':'Saved originals / drafts: product-এর বর্তমান thumbnail/gallery মুছবে না। নির্বাচিত পুরোনো original মুছলে সেটির restore history সব product থেকে সরবে। অন্য references থাকলে file রাখা হবে।';controls();}
 async function scan(){if(busy)return;if(loadedDone)reset();busy=true;stop=false;controls();el('is-progress').hidden=false;el('is-progress').removeAttribute('value');
  try{while(!stop){let j;
   try{if(kind==='version'){j=await api('/versions?cursor='+cursor+'&days='+days);cursor=j.cursor;add(j.rows);loadedDone=j.done;el('is-status').textContent='History cursor '+cursor+' · '+rows.size+' old images loaded';}
    else{j=scanId?await api('/scan/'+scanId,form({})):await api('/scan',form({days}));scanId=j.id;loadedDone=j.done;if(j.done)add(j.rows);el('is-status').textContent='Phase: '+j.phase+' · '+j.sources+' source files checked · '+j.checked+' generated copies checked · '+j.candidate_count+' candidates ('+mb(j.bytes)+')'+(j.truncated?' · প্রথম ২০০০টি; cleanup শেষে আবার scan করুন।':'');}
   }catch(e){if(e.status!==429)throw e;el('is-status').textContent=e.message+' · '+e.retry+' সেকেন্ড পরে scan আবার চলবে।';const until=Date.now()+e.retry*1000;while(!stop&&Date.now()<until)await wait(500);continue;}
   if(loadedDone||rows.size>=2000)break;await wait(1100);
  }el('is-status').textContent+=(stop?' · Paused / বিরতি':' · Load complete');}
  catch(e){el('is-status').textContent=e.message;}finally{busy=false;el('is-progress').hidden=true;controls();el('is-load').textContent=!loadedDone&&(cursor||scanId)?'Continue scan':'Scan / reload images';}
 }
 el('is-load').addEventListener('click',scan);el('is-kind').addEventListener('change',reset);el('is-days').addEventListener('change',reset);
 el('is-stop').addEventListener('click',()=>{stop=true;el('is-stop').disabled=true;el('is-status').textContent='Stopping after current request; completed deletion cannot be undone.';});
 el('is-all').addEventListener('change',()=>{root.querySelectorAll('[data-image-select]:not(:disabled)').forEach(n=>n.checked=el('is-all').checked);controls();});root.addEventListener('change',e=>{if(e.target.matches('[data-image-select]'))controls();});
 el('is-delete').addEventListener('click',()=>{selection=selected();if(!selection.length)return;el('is-delete-form').reset();el('is-loss').required=kind==='version';el('is-loss').parentElement.hidden=kind!=='version';el('is-confirm-summary').textContent=selection.length+' images · '+mb(selection.reduce((sum,k)=>sum+rows.get(k).bytes,0))+' selected. ব্যবহৃত/পরিবর্তিত file পাওয়া গেলে রাখা হবে।';el('is-confirm').hidden=false;el('is-confirm').scrollIntoView({behavior:'smooth',block:'center'});});
 el('is-cancel').addEventListener('click',()=>{if(!busy){el('is-confirm').hidden=true;el('is-delete-form').reset();}});
 el('is-delete-form').addEventListener('submit',async e=>{e.preventDefault();if(busy||!selection.length)return;let password=e.target.elements.password.value;const confirm=e.target.elements.confirm.value;busy=true;stop=false;controls();el('is-progress').hidden=false;el('is-progress').max=selection.length;el('is-progress').value=0;let done=0,freed=0,deleted=0;
  try{const chunk=kind==='version'?1:3;for(let i=0;i<selection.length&&!stop;i+=chunk){const keys=selection.slice(i,i+chunk);const body=form({kind,days,password,confirm});
    if(kind==='optimized'){body.set('scan',scanId);keys.forEach(k=>body.append('names[]',rows.get(k).name));}else{const row=rows.get(keys[0]);body.set('upload_id',row.id);body.set('sha256',row.sha256);}
    const result=await api('/delete',body);freed+=result.freed_bytes;deleted+=result.deleted;done+=keys.length;
    keys.forEach(key=>{rows.delete(key);Array.from(el('is-rows').children).find(n=>n.dataset.imageRow===key)?.remove();});
    el('is-progress').value=done;el('is-status').textContent=deleted+' images deleted · '+mb(freed)+' released · '+done+'/'+selection.length+' processed';controls();if(i+chunk<selection.length&&!stop)await wait(1100);
   }el('is-status').textContent=deleted+' images deleted · '+mb(freed)+' released.'+(stop?' Stopped after current batch.':' Cleanup complete.');el('is-confirm').hidden=true;}
  catch(error){el('is-status').textContent=deleted+' images deleted · '+mb(freed)+' released. '+error.message+' · No automatic delete retry. তালিকা rescan করে তারপর চেষ্টা করুন।';}
  finally{password='';e.target.reset();selection=[];busy=false;el('is-progress').hidden=true;controls();}
 });
 reset();
})();
