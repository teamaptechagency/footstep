/* Admin-only, on-demand filesystem metadata; no background scan on public requests. */
(() => {
 'use strict';
 const root=document.getElementById('backup-storage'); if(!root)return;
 const el=id=>document.getElementById(id), base=root.dataset.base, token=root.dataset.token;
 const mb=n=>(Number(n||0)/1048576).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})+' MB';
 const node=(tag,text,cls)=>{const n=document.createElement(tag);if(text!==undefined)n.textContent=text;if(cls)n.className=cls;return n;};
 const store={get(k){try{return sessionStorage.getItem(k);}catch(e){return null;}},set(k,v){try{sessionStorage.setItem(k,v);}catch(e){}}};
 let rows=[],deleting=false,confirmed=[],scanning=false,scanId=store.get('footstep-storage-scan')||'',generation=0;
 async function request(path,body){
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),45000);
  try{const r=await fetch(base+path,{method:body?'POST':'GET',credentials:'same-origin',cache:'no-store',redirect:'error',headers:{Accept:'application/json','X-CSRF-TOKEN':token},body,signal:controller.signal});
   let j;try{j=await r.json();}catch(e){throw new Error('Server response পড়া যায়নি। Login, disk quota ও hosting error log দেখুন।');}
   if(r.status===429)throw Object.assign(new Error('অনুরোধের সীমা হয়েছে। কিছুক্ষণ অপেক্ষা করুন।'),{httpStatus:429,retryAfter:Math.max(5,Math.min(3600,Number(r.headers.get('Retry-After')||j.retry_after)||60))});if(!r.ok){const details=j.errors?Object.values(j.errors).flat().join(' '):'';throw new Error((j.message||'Request failed')+' '+details+' (HTTP '+r.status+')'+(j.request_id?' · ID: '+j.request_id:''));}return j;
  }catch(e){if(e.name==='AbortError')throw new Error('Request timeout. Refresh list before retrying; কাজ হয়ে থাকতে পারে।');throw e;}finally{clearTimeout(timer);}
 }
 const selected=()=>Array.from(root.querySelectorAll('[data-backup-select]:checked')).map(n=>n.value);
 function updateSelection(){const ids=selected();el('bs-delete').disabled=deleting||!ids.length;el('bs-delete').textContent='Delete selected ('+ids.length+')';const count=rows.filter(r=>r.can_delete).length;el('bs-all').checked=count>0&&ids.length===count;el('bs-all').indeterminate=ids.length>0&&ids.length<count;el('bs-all').disabled=deleting||count===0;}
 function button(text,attr,id){const b=node('button',text,'btn btn-sm btn-light mr-1 mb-1');b.type='button';b.setAttribute(attr,id);return b;}
 function drawInventory(data){
  rows=data.rows||[];const tbody=el('bs-rows');tbody.replaceChildren();
  el('bs-totals').textContent='Known backup ZIPs: '+mb(data.archive_bytes)+' · Deletable backup ZIP + records: '+mb(data.deletable_bytes)+(data.unreadable_records?' · Unreadable record আছে: safe cleanup আপাতত বন্ধ।':'');
  rows.forEach(r=>{
   const tr=node('tr'),select=node('td'),check=node('input');check.type='checkbox';check.value=r.id;check.disabled=!r.can_delete;check.setAttribute('data-backup-select','');check.setAttribute('aria-label','Select backup '+r.id);select.append(check);
   const ref=node('td',r.created_at||'Unknown date');ref.append(node('small',r.id,'d-block text-muted'));ref.style.overflowWrap='anywhere';
   const status=node('td',r.kind+' · '+r.state);status.append(node('small',r.phase,'d-block text-muted'));
   const size=node('td',r.archive_exists?mb(r.archive_bytes):'ZIP নেই / Missing');size.append(node('small','Record: '+mb(r.record_bytes),'d-block text-muted'));
   const actions=node('td');actions.append(button('Progress / results','data-wb-watch',r.id));
   if(r.state==='complete'&&['backup','inspect'].includes(r.kind)&&r.archive_exists){const a=node('a','Download ZIP','btn btn-sm btn-light mr-1 mb-1');a.href=base+'/'+encodeURIComponent(r.id)+'/download';const checkButton=button('Check files','data-wb-select',r.id),restoreButton=button('Restore data','data-wb-restore',r.id);restoreButton.classList.remove('btn-light');restoreButton.classList.add('btn-success');actions.append(a,checkButton,restoreButton);}
   if(r.can_delete){const del=button('Delete','data-delete-one',r.id);del.className='btn btn-sm btn-outline-danger';actions.append(del);}else actions.append(node('small',r.blocked_reason||'Protected','d-block text-muted'));
   tr.append(select,ref,status,size,actions);tbody.append(tr);
  });
  if(!rows.length){const tr=node('tr'),td=node('td','No backup operations found.');td.colSpan=5;tr.append(td);tbody.append(tr);}
  const list=el('bs-orphans');list.replaceChildren();(data.orphan_archives||[]).forEach(a=>list.append(node('li',a.file+' · '+mb(a.bytes))));if(!list.children.length)list.append(node('li','None found / পাওয়া যায়নি।'));updateSelection();
 }
 async function refresh(){el('bs-refresh').disabled=true;try{drawInventory(await request('/storage/inventory'));}catch(e){el('bs-message').textContent=e.message;}finally{el('bs-refresh').disabled=false;}}
 function confirmDelete(ids){if(deleting||!ids.length)return;confirmed=ids.slice();el('bs-delete-form').reset();el('bs-confirm-summary').textContent=ids.length+' backup operations selected. ZIP + records shown: '+mb(rows.filter(r=>ids.includes(r.id)).reduce((n,r)=>n+r.archive_bytes+r.record_bytes,0))+'. Temporary files থাকলে সেগুলোও মুছবে।';el('bs-confirm').hidden=false;el('bs-confirm').scrollIntoView({behavior:'smooth',block:'center'});}
 el('bs-refresh').addEventListener('click',refresh);el('bs-all').addEventListener('change',()=>{root.querySelectorAll('[data-backup-select]:not(:disabled)').forEach(n=>n.checked=el('bs-all').checked);updateSelection();});
 root.addEventListener('change',e=>{if(e.target.matches('[data-backup-select]'))updateSelection();});
 root.addEventListener('click',e=>{const b=e.target.closest('[data-delete-one]');if(b)confirmDelete([b.dataset.deleteOne]);});
 el('bs-delete').addEventListener('click',()=>confirmDelete(selected()));el('bs-cancel').addEventListener('click',()=>{if(!deleting){el('bs-confirm').hidden=true;el('bs-delete-form').reset();confirmed=[];}});
 el('bs-delete-form').addEventListener('submit',async e=>{
  e.preventDefault();if(deleting||!confirmed.length)return;deleting=true;el('bs-confirm-delete').disabled=true;updateSelection();
  const body=new FormData(e.target);confirmed.forEach(id=>body.append('ids[]',id));
  try{const j=await request('/storage/delete',body);el('bs-message').textContent=j.deleted+' operations deleted · '+mb(j.freed_bytes)+' released. '+j.message;el('bs-confirm').hidden=true;e.target.reset();confirmed=[];await refresh();}
  catch(error){el('bs-message').textContent=error.message;await refresh();}finally{deleting=false;el('bs-confirm-delete').disabled=false;updateSelection();}
 });
 function drawScan(j){
  el('bs-scan-status').textContent=(j.done?'Scan complete / সম্পন্ন':'Scanned so far / এখন পর্যন্ত')+': '+j.files.toLocaleString()+' files · '+mb(j.bytes)+' · '+j.skipped+' skipped · Filesystem free: '+mb(j.filesystem_free_bytes)+' (account quota নয়)';
  const folders=el('bs-folders');folders.replaceChildren();j.groups.forEach(g=>{const tr=node('tr');tr.append(node('td',g.path),node('td',g.files.toLocaleString()),node('td',mb(g.bytes)));folders.append(tr);});
  const archives=el('bs-archives');archives.replaceChildren();j.archives.forEach(a=>{const tr=node('tr');tr.append(node('td',a.path),node('td',mb(a.bytes)));archives.append(tr);});el('bs-archive-title').textContent='ZIP / archive files: '+j.archive_count+' found · largest '+j.archives.length+' listed';
 }
 function scanButtons(){el('bs-scan').disabled=scanning;el('bs-scan').textContent=scanId?'Resume storage scan':'Scan storage';el('bs-pause').disabled=!scanning;el('bs-progress').hidden=!scanning;}
 async function runScan(){
  if(scanning)return;scanning=true;const ticket=++generation;scanButtons();
  try{
   if(!scanId){const j=await request('/storage/scan',new FormData());scanId=j.id;store.set('footstep-storage-scan',scanId);drawScan(j);}
   while(scanning&&ticket===generation){let j;try{j=await request('/storage/scan/'+encodeURIComponent(scanId),new FormData());}catch(error){if(error.httpStatus!==429)throw error;el('bs-scan-status').textContent='Rate limit: '+error.retryAfter+' সেকেন্ড পর scan আবার চলবে।';const until=Date.now()+error.retryAfter*1000;while(scanning&&ticket===generation&&Date.now()<until)await new Promise(resolve=>setTimeout(resolve,500));continue;}drawScan(j);if(j.done){scanId='';store.set('footstep-storage-scan','');break;}await new Promise(resolve=>setTimeout(resolve,1100));}
  }catch(e){el('bs-scan-status').textContent=e.message;scanId='';store.set('footstep-storage-scan','');}
  finally{scanning=false;scanButtons();}
 }
 el('bs-scan').addEventListener('click',runScan);el('bs-pause').addEventListener('click',()=>{scanning=false;el('bs-pause').disabled=true;el('bs-scan-status').textContent='Pausing after current scan batch…';});
 scanButtons();refresh();
})();
