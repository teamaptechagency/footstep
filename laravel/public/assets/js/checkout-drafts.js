(function(){'use strict';function init(){if(navigator.doNotTrack==='1'||navigator.globalPrivacyControl===true)return;
 document.querySelectorAll('form[data-checkout-draft]').forEach(form=>{
  const direct=form.dataset.checkoutDraft==='direct',dialog=form.closest('dialog');let disabled=false,completed=false,timer=null,lastSent=0,lastInteraction=Date.now(),hadPhone=false;
  function active(){return !direct||!!dialog?.open;}
  function payload(){const data=new FormData();data.set('_token',form.elements._token.value);data.set('channel',direct?'direct':'cart');data.set('checkout_draft_token',form.elements.checkout_draft_token.value);['name','phone','address','city','whatsapp_phone','product_id','stock_id','quantity'].forEach(name=>{if(form.elements[name])data.set(name,form.elements[name].value);});if(form.elements.whatsapp_same)data.set('whatsapp_same',form.elements.whatsapp_same.checked?'1':'0');return data;}
  function send(final=false,force=false){if(disabled||completed||(!active()&&!force))return;clearTimeout(timer);const data=payload();const digits=String(data.get('phone')||'').replace(/[০-৯]/g,c=>String('০১২৩৪৫৬৭৮৯'.indexOf(c))).replace(/\D/g,'');const hasPhone=digits.length>=10&&digits.length<=15;if(!hasPhone&&!hadPhone)return;hadPhone=hasPhone;lastSent=Date.now();if(final&&navigator.sendBeacon){navigator.sendBeacon(form.dataset.draftUrl,data);return;}fetch(form.dataset.draftUrl,{method:'POST',body:data,keepalive:true,credentials:'same-origin',headers:{'Accept':'application/json'}}).then(response=>{if(response.status===204)disabled=true;}).catch(()=>{});}
  function schedule(){lastInteraction=Date.now();clearTimeout(timer);timer=setTimeout(()=>send(),Math.max(800,2500-(Date.now()-lastSent)));}
  form.addEventListener('input',schedule);form.addEventListener('change',schedule);form.addEventListener('focusout',schedule);form.addEventListener('checkout:completed',()=>{completed=true;clearTimeout(timer);});
  if(direct){form.addEventListener('checkout:opened',()=>{lastInteraction=Date.now();send();});dialog.addEventListener('close',()=>send(true,true));}else setTimeout(()=>send(),1000);
  setInterval(()=>{if(active()&&document.visibilityState==='visible'&&document.hasFocus()&&Date.now()-lastInteraction<60000)send();},30000);
  window.addEventListener('pagehide',()=>send(true));document.addEventListener('visibilitychange',()=>{if(document.visibilityState==='hidden')send(true);});
 });
}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();})();
