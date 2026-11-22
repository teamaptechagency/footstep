(() => {
 'use strict';const root=document.getElementById('rollback-detail');if(!root)return;
 const check=document.getElementById('rb-check'),status=document.getElementById('rb-status'),submit=document.getElementById('rb-submit'),table=document.getElementById('rb-files');if(!check)return;
 check.addEventListener('click',async()=>{
  check.disabled=true;if(submit)submit.disabled=true;status.textContent='Checking saved and installed file checksums…';table.hidden=true;
  const controller=new AbortController(),timer=setTimeout(()=>controller.abort(),45000);
  try{
   const r=await fetch(root.dataset.url,{credentials:'same-origin',cache:'no-store',redirect:'error',headers:{Accept:'application/json'},signal:controller.signal});const j=await r.json();if(!r.ok)throw new Error(j.message||'Rollback check failed.');
   const next=j.next;if(!next)throw new Error('No rollback snapshot available.');const tbody=table.querySelector('tbody');tbody.replaceChildren();
   next.files.forEach(f=>{const tr=document.createElement('tr');[f.path,f.action==='remove_added_file'?'Remove file added by update / নতুন file সরবে':'Restore previous contents / আগের file ফিরবে',(f.restore_bytes/1048576).toFixed(2)+' MB'].forEach(text=>{const td=document.createElement('td');td.textContent=text;tr.append(td);});tbody.append(tr);});table.hidden=false;
   status.textContent=next.from+' → '+next.target+' · '+next.restore_count+' restore · '+next.remove_count+' remove. '+(next.ready?'Checks passed / যাচাই সফল।':next.issues.join(' · '));
   if(submit){submit.disabled=!next.ready;submit.textContent=next.ready?'Roll back '+next.from+' → '+next.target:'Rollback blocked / ফিরিয়ে নেওয়া যাবে না';}
  }catch(e){status.textContent=e.name==='AbortError'?'Check timeout. Retry before rollback.':e.message;}finally{clearTimeout(timer);check.disabled=false;}
 });
})();
