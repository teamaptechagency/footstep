(function(){'use strict';function init(){
const dialog=document.getElementById('footstep-direct-dialog'), source=document.getElementById('option-choice-form'), form=document.getElementById('footstep-direct-form');if(!dialog||!source||!form)return;
const error=document.getElementById('direct-error'), confirm=document.getElementById('direct-confirm'),stock=form.elements.stock_id;
let quote=null,busy=false,opening=false,quoteSequence=0,quoteAbort=null,lastQuoteData=null,previousFocus=null,previousOverflow='';
const names=['name','phone','address'],key='footstep.checkout.guest.v1';
const money=value=>'৳'+Number(value).toLocaleString('en-BD',{minimumFractionDigits:2,maximumFractionDigits:2});
function message(text){error.textContent=text||'';error.hidden=!text;}
function sync(){}
function restore(){if(dialog.dataset.member==='0'){try{const saved=JSON.parse(localStorage.getItem(key)||'{}');names.forEach(n=>{if(typeof saved[n]==='string')form.elements[n].value=saved[n];});if(saved.city)Array.from(form.querySelectorAll('[name="city"]')).forEach(r=>r.checked=r.value===saved.city);}catch(e){}}sync();}
function save(){if(dialog.dataset.member!=='0')return;try{const saved=JSON.parse(localStorage.getItem(key)||'{}');names.forEach(n=>saved[n]=form.elements[n].value);saved.city=form.elements.city.value;localStorage.setItem(key,JSON.stringify(saved));}catch(e){}}
function total(){if(quote?.fees)form.querySelectorAll('[name="city"]').forEach(input=>{const fee=quote.fees[input.value];input.dataset.fee=fee;const label=input.closest('label')?.querySelector('small');if(label)label.textContent='ডেলিভারি — ৳'+fee;});const city=form.querySelector('[name="city"]:checked'),fee=Number(quote?.fees?.[city?.value]??city?.dataset.fee??0);document.getElementById('direct-fee').textContent=city?money(fee):'এলাকা নির্বাচন করুন';document.getElementById('direct-subtotal').textContent=quote?money(quote.subtotal):'—';document.getElementById('direct-total').textContent=quote?money(quote.subtotal+fee):'—';confirm.disabled=busy||!quote;stock.disabled=busy;form.querySelectorAll('[name="addon_ids[]"]').forEach(input=>input.disabled=busy);}
const loading=document.getElementById('direct-loading'),retry=document.getElementById('direct-retry');
async function fetchQuote(data){const sequence=++quoteSequence;if(quoteAbort)quoteAbort.abort();const controller=new AbortController();quoteAbort=controller;lastQuoteData=data;quote=null;total();loading.hidden=false;retry.hidden=true;form.setAttribute('aria-busy','true');const timer=setTimeout(()=>controller.abort(),15000);try{const response=await fetch(dialog.dataset.quote,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:data,signal:controller.signal});if(response.redirected||response.status===419||response.status===401)throw Error('Session শেষ হয়েছে। পেজ refresh করে আবার চেষ্টা করুন।');const result=await response.json();if(sequence!==quoteSequence)return false;if(!response.ok)throw Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'সাইজ নির্বাচন করুন।');if(!result.stock_id||!Number.isFinite(Number(result.subtotal)))throw Error('মূল্য যাচাই করা যায়নি। আবার চেষ্টা করুন।');quote=result;stock.value=String(result.stock_id);form.elements.quantity.value=result.quantity;document.getElementById('direct-quantity').textContent=result.quantity>1?' · '+result.quantity+' জোড়া':'';message('');total();return true;}catch(e){if(sequence!==quoteSequence)return false;message(e.name==='AbortError'?'Server response পেতে সময় লাগছে। আবার যাচাই করুন।':e.message||'আবার চেষ্টা করুন।');retry.hidden=false;total();return false;}finally{clearTimeout(timer);if(sequence===quoteSequence){loading.hidden=true;form.setAttribute('aria-busy','false');quoteAbort=null;}}}
retry.addEventListener('click',()=>{if(lastQuoteData&&!busy&&dialog.open){message('');fetchQuote(lastQuoteData);}});
window.footstepDirectCheckout=async function(){
 if(dialog.open||opening)return;
 if(typeof window.checkAddToCartValidity==='function'&&!window.checkAddToCartValidity()){
  source.scrollIntoView({block:'center',behavior:'smooth'});
  if(window.AIZ?.plugins?.notify)window.AIZ.plugins.notify('warning','আগে সাইজ নির্বাচন করুন।');
  return;
 }
 opening=true;restore();message('');
 const data=new FormData(source);data.set('_token',form.elements._token.value);data.set('product_id',form.elements.product_id.value);data.set('quantity',source.querySelector('[name="quantity"]')?.value||'1');if(source.dataset.quick!=='1')data.delete('stock_id');form.querySelectorAll('[name="addon_ids[]"]:checked').forEach(input=>data.append('addon_ids[]',input.value));form.elements.quantity.value=data.get('quantity');data.set('exit_offer_token',form.elements.exit_offer_token.value);data.set('checkout_token',form.elements.checkout_token.value);
 // Select the same visible variant before the background quote arrives.
 const parts=[];if(data.get('color'))parts.push(data.get('color'));source.querySelectorAll('input[type="radio"]:checked').forEach(input=>{if(input.name.indexOf('attribute_id_')===0)parts.push(input.value.replace(/ /g,''));});
 const selected=Array.from(stock.options).find(option=>!option.disabled&&option.dataset.variant===parts.join('-'));stock.value=source.dataset.quick==='1'?data.get('stock_id'):(selected?selected.value:'');quote=null;total();
 previousFocus=document.activeElement;previousOverflow=document.body.style.overflow;document.body.style.overflow='hidden';document.body.classList.add('footstep-direct-open');dialog.showModal();opening=false;form.dispatchEvent(new Event('checkout:opened'));window.dispatchEvent(new Event('scroll'));
 // The private/no-store quick HTML already contains a fresh server quote for its initial size.
 // Confirm always revalidates on the server; changing size still requests a new quote.
 let initial=null;try{initial=JSON.parse(source.dataset.initialQuote||'null');}catch(e){}
 if(source.dataset.quick==='1'&&!form.elements.exit_offer_token.value&&initial&&String(initial.stock_id)===String(data.get('stock_id'))&&Number(initial.quantity)===Number(data.get('quantity'))&&Number.isFinite(Number(initial.subtotal))){
  quote=initial;stock.value=String(initial.stock_id);form.elements.quantity.value=initial.quantity;
  document.getElementById('direct-quantity').textContent=initial.quantity>1?' · '+initial.quantity+' জোড়া':'';
  loading.hidden=true;retry.hidden=true;total();
 }else await fetchQuote(data);
};
document.querySelectorAll('[data-direct-checkout]').forEach(b=>b.addEventListener('click',window.footstepDirectCheckout));
const help=document.getElementById('direct-exit-help'),helpTitle=document.getElementById('direct-exit-title'),helpLink=document.getElementById('direct-exit-whatsapp'),freeOffer=document.querySelector('.direct-free-offer'),freeAccept=document.getElementById('direct-exit-free-accept');
function hideHelp(){help.hidden=true;form.inert=false;dialog.querySelector('[data-direct-close]').focus();}
function requestClose(){
 if(busy)return;
 if(!help.hidden){hideHelp();dialog.close();return;}
 if((!form.elements.phone.value.trim()||!dialog.dataset.whatsapp)&&!freeOffer){dialog.close();return;}
 const variant=stock.selectedOptions[0]?.dataset.variant||stock.selectedOptions[0]?.textContent||'Standard';
 const details=['অর্ডার করতে সমস্যা হচ্ছে। অনুগ্রহ করে সাহায্য করুন।','পণ্য: '+dialog.dataset.productName,'সাইজ: '+variant,'পরিমাণ: '+form.elements.quantity.value];
 if(quote)details.push('পণ্যমূল্য: '+money(quote.subtotal));
 form.querySelectorAll('[name="addon_ids[]"]:checked').forEach(input=>details.push('Add-on: '+input.closest('label').textContent.trim()));
 details.push(new URL(dialog.dataset.productUrl,location.origin).href);
 helpLink.href='https://wa.me/'+dialog.dataset.whatsapp+'?text='+encodeURIComponent(details.join('\n'));
 help.hidden=false;form.inert=true;helpTitle.focus();
}
if(freeAccept)freeAccept.addEventListener('click',async()=>{
 if(busy)return;
 let ids=[];try{ids=JSON.parse(freeOffer.dataset.exitOfferIds||'[]').map(Number);}catch(e){return;}
 form.elements.exit_offer_token.value=freeOffer.dataset.exitOfferToken;
 form.querySelectorAll('[name="addon_ids[]"]').forEach(input=>{if(ids.includes(Number(input.value)))input.checked=true;});
 hideHelp();
 const valid=await fetchQuote(new FormData(form));
 if(valid){freeAccept.textContent='উপহার যোগ হয়েছে — অর্ডার সম্পন্ন করুন →';form.querySelectorAll('[name="addon_ids[]"]').forEach(input=>{if(ids.includes(Number(input.value))){const price=input.closest('label').querySelector('b');if(price)price.textContent='ফ্রি / ৳0.00';}});const note=form.querySelector('.direct-addons small');if(note)note.textContent='Free offer সক্রিয় — নির্বাচিত উপহারের জন্য কোনো extra charge নেই।';}
 form.elements.name.focus();
});
document.getElementById('direct-exit-return').addEventListener('click',hideHelp);
document.getElementById('direct-exit-dismiss').addEventListener('click',()=>{hideHelp();dialog.close();});
window.addEventListener('message',e=>{if(window.parent!==window&&e.origin===location.origin&&e.source===window.parent&&e.data?.type==='footstep-checkout-request-close')requestClose();});
dialog.querySelector('[data-direct-close]').addEventListener('click',requestClose);
dialog.addEventListener('cancel',e=>{e.preventDefault();if(!help.hidden)hideHelp();else requestClose();});
dialog.addEventListener('click',e=>{if(e.target===dialog&&!busy){const rect=dialog.getBoundingClientRect();if(e.clientX<rect.left||e.clientX>rect.right||e.clientY<rect.top||e.clientY>rect.bottom)requestClose();}});
dialog.addEventListener('close',()=>{help.hidden=true;form.inert=false;quoteSequence++;if(quoteAbort)quoteAbort.abort();quoteAbort=null;loading.hidden=true;form.setAttribute('aria-busy','false');document.body.style.overflow=previousOverflow;document.body.classList.remove('footstep-direct-open');previousFocus?.focus();window.dispatchEvent(new Event('scroll'));});
form.querySelectorAll('[name="addon_ids[]"]').forEach(input=>input.addEventListener('change',()=>{if(!busy)fetchQuote(new FormData(form));}));
stock.addEventListener('change',async()=>{if(busy)return;const data=new FormData(form);await fetchQuote(data);});
form.addEventListener('input',()=>{sync();save();});form.addEventListener('change',()=>{sync();save();total();});
form.addEventListener('submit',async e=>{e.preventDefault();if(busy||!quote||!form.reportValidity())return;busy=true;message('');total();save();try{const response=await fetch(dialog.dataset.submit,{method:'POST',headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'},body:(()=>{const data=new FormData(form);data.set('stock_id',stock.value);data.delete('addon_ids[]');form.querySelectorAll('[name="addon_ids[]"]:checked').forEach(input=>data.append('addon_ids[]',input.value));return data;})()});const result=await response.json();if(!response.ok)throw Error(Object.values(result.errors||{}).flat().join(' ')||result.message||'অর্ডার হয়নি। আবার চেষ্টা করুন।');if(!result.redirect)throw Error('অর্ডার নিশ্চিত করা যায়নি।');form.dispatchEvent(new Event('checkout:completed'));if(source.dataset.quick==='1'&&window.parent!==window)window.parent.location.assign(result.redirect);else window.location.assign(result.redirect);}catch(e){busy=false;message(e.message);total();error.scrollIntoView({block:'nearest',behavior:'smooth'});}});
}if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();})();
