@php $customerConfirmWhatsApp=\App\Services\OrderConfirmationWhatsApp::link($order); @endphp
<div class="customer-invoice-actions" style="margin-top:20px">
@if(\App\Services\OrderConfirmationWhatsApp::phone(get_setting('order_confirmation_whatsapp')?:get_setting('contact_phone')))
<section class="invoice-whatsapp-card" aria-labelledby="invoice-whatsapp-title-{{ $order->id }}">
<div class="invoice-whatsapp-heading"><span class="invoice-whatsapp-icon" aria-hidden="true"><svg width="25" height="25" viewBox="0 0 24 24" fill="none"><path d="M20.5 11.5a8.5 8.5 0 0 1-12.7 7.4L3 20.5l1.6-4.8a8.5 8.5 0 1 1 15.9-4.2Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="m8.4 7.8 1.1 2-1 1.1a9.2 9.2 0 0 0 4.4 4.3l1.1-1 2 1.1c.3.2.4.5.2.8-.7 1.3-1.8 1.5-3.2.9-3.4-1.4-6-4-7.1-7.1-.4-1.3-.1-2.2 1-2.8.5-.2 1.1 0 1.5.7Z" fill="currentColor"/></svg></span><div><span class="invoice-whatsapp-eyebrow">WHATSAPP CONFIRMATION</span><h3 id="invoice-whatsapp-title-{{ $order->id }}">আপনার অর্ডারটি কনফার্ম করুন</h3></div></div>
<p>অর্ডার নম্বরসহ message তৈরি আছে। WhatsApp খুলে শুধু <strong>Send</strong> করুন।</p>
<a class="invoice-whatsapp-cta" data-order-whatsapp-confirm data-expires="{{ \App\Services\OrderConfirmationWhatsApp::expiresAt($order)*1000 }}" data-server-now="{{ time()*1000 }}" @if($customerConfirmWhatsApp) href="{{ $customerConfirmWhatsApp }}" @else aria-disabled="true" tabindex="-1" @endif target="_blank" rel="noopener noreferrer">WhatsApp-এ এখনই অর্ডারটা কনফার্ম করুন <span aria-hidden="true">↗</span></a><span class="invoice-wa-timer" data-wa-timer role="status"></span>
</section>
<div class="invoice-wa-mobile" data-invoice-wa-mobile><a class="invoice-whatsapp-cta" data-order-whatsapp-confirm data-expires="{{ \App\Services\OrderConfirmationWhatsApp::expiresAt($order)*1000 }}" data-server-now="{{ time()*1000 }}" @if($customerConfirmWhatsApp) href="{{ $customerConfirmWhatsApp }}" @else aria-disabled="true" tabindex="-1" @endif target="_blank" rel="noopener noreferrer">WhatsApp-এ এখনই অর্ডারটা কনফার্ম করুন <span aria-hidden="true">↗</span></a><span class="invoice-wa-timer" data-wa-timer role="status"></span></div>
@endif

</div>
<style>
.invoice-wa-timer{display:block;margin-top:8px;font-size:12px;font-weight:700;color:#17452c;text-align:center}.invoice-whatsapp-cta[aria-disabled="true"]{background:#738079!important;box-shadow:none;animation:none!important;cursor:not-allowed;opacity:.8}

.invoice-whatsapp-card{padding:22px;margin:20px 0;border:1px solid #a9d7bc;border-top:4px solid #128c4b;border-radius:14px;background:linear-gradient(135deg,#eefaf2,#f9fcfa);box-shadow:0 5px 18px #1665340a;min-width:0}
.invoice-whatsapp-heading{display:flex;gap:12px;align-items:center}.invoice-whatsapp-icon{display:grid;place-items:center;flex:0 0 46px;height:46px;border-radius:13px;background:#128c4b;color:white}.invoice-whatsapp-eyebrow{display:block;font-size:10px;letter-spacing:1.3px;font-weight:700;color:#227246;margin-bottom:4px}.invoice-whatsapp-card h3{font-size:20px;line-height:1.5;color:#17452c;margin:0;font-weight:700}.invoice-whatsapp-card p{font-size:13px;line-height:1.8;margin:14px 0 18px;color:#486856}
.invoice-whatsapp-cta{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;box-sizing:border-box;min-height:58px;padding:14px 18px;border-radius:10px;background:#087d3e;color:#fff!important;text-decoration:none!important;text-align:center;font-size:16px;line-height:1.7;font-weight:700;box-shadow:0 4px 10px #087d3e20}.invoice-whatsapp-cta:hover{background:#066832}.invoice-whatsapp-cta:focus-visible{outline:3px solid #72b996;outline-offset:4px}.invoice-whatsapp-cta span{font-size:21px;flex-shrink:0}.invoice-wa-mobile{display:none}
@media(max-width:600px){.invoice-whatsapp-card{padding:18px 15px;border-radius:12px}.invoice-whatsapp-card h3{font-size:18px}.invoice-whatsapp-cta{font-size:14px;padding:13px 12px}.invoice-whatsapp-eyebrow{font-size:9px;letter-spacing:1px}.invoice-whatsapp-icon{flex-basis:40px;height:40px}.invoice-whatsapp-card p{font-size:12px}}
@media(max-width:767px){
 .invoice-whatsapp-card .invoice-whatsapp-cta{display:flex}
 .invoice-wa-mobile{display:block;position:fixed;left:0;right:0;bottom:var(--invoice-wa-nav-offset,0px);z-index:1030;padding:12px 16px calc(12px + env(safe-area-inset-bottom,0px));background:#fffffff5;border-top:1px solid #d6e9dd;box-shadow:0 -4px 20px #173b2a12;box-sizing:border-box}
 .invoice-wa-mobile .invoice-whatsapp-cta{font-size:14px;line-height:1.7;min-height:54px;animation:invoice-wa-pulse 2.4s ease-out infinite}
 body:has(.aiz-mobile-bottom-nav) .invoice-wa-mobile{--invoice-wa-nav-offset:80px}
 body.has-invoice-wa-dock{padding-bottom:calc(var(--invoice-wa-clearance,96px) + env(safe-area-inset-bottom,0px))!important}
}
@keyframes invoice-wa-pulse{0%,100%{box-shadow:0 3px 10px #087d3e26,0 0 0 0 #16a34a55}65%{box-shadow:0 3px 10px #087d3e26,0 0 0 8px #16a34a00}}
@media(prefers-reduced-motion:reduce){.invoice-wa-mobile .invoice-whatsapp-cta{animation:none}}
@media print{.customer-invoice-actions,.invoice-wa-mobile{display:none!important}body.has-invoice-wa-dock{padding-bottom:0!important}}
</style>
<script>
(function(){
 function countdown(){document.querySelectorAll('[data-order-whatsapp-confirm]').forEach(function(a){var offset=Date.now()-Number(a.dataset.serverNow),deadline=Number(a.dataset.expires);function tick(){var left=Math.max(0,Math.ceil((deadline-(Date.now()-offset))/1000));var label=a.nextElementSibling;if(label&&label.hasAttribute('data-wa-timer'))label.textContent=left?'WhatsApp confirmation · '+Math.floor(left/60)+':'+String(left%60).padStart(2,'0')+' বাকি':'১০ মিনিট শেষ — WhatsApp confirmation বন্ধ';if(!left){a.removeAttribute('href');a.removeAttribute('target');a.setAttribute('aria-disabled','true');a.tabIndex=-1;clearInterval(timer);}}var timer=setInterval(tick,1000);tick();a.addEventListener('click',function(e){if(a.getAttribute('aria-disabled')==='true')e.preventDefault();});});}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',countdown,{once:true});else countdown();

 function init(){
  if(window.footstepInvoiceWaDockReady)return;
  var bars=document.querySelectorAll('[data-invoice-wa-mobile]');if(!bars.length)return;
  window.footstepInvoiceWaDockReady=true;
  var bar=bars[0];for(var i=1;i<bars.length;i++)bars[i].remove();
  var nav=document.querySelector('.aiz-mobile-bottom-nav');
  document.body.classList.add('has-invoice-wa-dock');
  function update(){
   var navHeight=nav&&getComputedStyle(nav).display!=='none'?nav.getBoundingClientRect().height:0;
   var offset=navHeight?Math.ceil(navHeight)+24:0;
   bar.style.setProperty('--invoice-wa-nav-offset',offset+'px');
   document.body.style.setProperty('--invoice-wa-clearance',Math.ceil(bar.getBoundingClientRect().height)+offset+20+'px');
  }
  update();window.addEventListener('resize',update,{passive:true});
  if(window.ResizeObserver){var observer=new ResizeObserver(update);observer.observe(bar);if(nav)observer.observe(nav);}
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init,{once:true});else init();
}());
</script>
