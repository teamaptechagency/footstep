document.addEventListener('DOMContentLoaded',function(){
 const cart=document.getElementById('bazar-cart');let lastFocus=null;
 const closingTimers=new WeakMap();
 function openDrawer(dialog,button){
  if(!dialog||dialog.open)return;
  lastFocus=button;clearTimeout(closingTimers.get(dialog));
  dialog.classList.remove('bazar-drawer-visible');dialog.showModal();
  document.documentElement.classList.add('bazar-drawer-active');
  // Commit the off-screen starting position before moving to the open position.
  void dialog.offsetWidth;
  requestAnimationFrame(()=>requestAnimationFrame(()=>{if(dialog.open&&!closingTimers.has(dialog)){dialog.classList.add('bazar-drawer-visible');if(dialog.id==='bazar-menu')document.documentElement.classList.add('bazar-menu-pushed');}}));
  if(dialog===cart)loadCart();
 }
 function closeDrawer(dialog){
  if(!dialog.open||closingTimers.has(dialog))return;
  dialog.classList.remove('bazar-drawer-visible');
  if(dialog.id==='bazar-menu')document.documentElement.classList.remove('bazar-menu-pushed');
  const done=()=>{clearTimeout(closingTimers.get(dialog));closingTimers.delete(dialog);dialog.removeEventListener('transitionend',onEnd);if(dialog.open)dialog.close();};
  const onEnd=event=>{if(event.target===dialog&&event.propertyName==='transform')done();};
  dialog.addEventListener('transitionend',onEnd);
  closingTimers.set(dialog,setTimeout(done,window.matchMedia('(prefers-reduced-motion: reduce)').matches?0:380));
 }
 async function loadCart(){const target=cart.querySelector('[data-bazar-cart-content]');target.textContent='Loading your cart…';try{const res=await fetch(cart.dataset.cartUrl,{headers:{'X-Requested-With':'XMLHttpRequest'},cache:'no-store'});if(!res.ok)throw Error();target.innerHTML=await res.text();const count=res.headers.get('X-Cart-Count'),total=res.headers.get('X-Cart-Total');if(count!==null)document.querySelectorAll('.bazar-cart-count').forEach(el=>el.textContent=count);if(total!==null)document.querySelectorAll('.bazar-cart-total').forEach(el=>el.textContent=decodeURIComponent(total));}catch(e){target.textContent='Cart could not load. ';const a=document.createElement('a');a.href='/cart';a.textContent='Open checkout';target.append(a);}}
 document.querySelectorAll('[data-bazar-open]').forEach(button=>button.addEventListener('click',()=>{const dialog=document.getElementById(button.dataset.bazarOpen);openDrawer(dialog,button);}));
 document.querySelectorAll('.bazar-drawer').forEach(dialog=>{dialog.querySelector('[data-bazar-close]').addEventListener('click',()=>closeDrawer(dialog));dialog.addEventListener('click',event=>{if(event.target===dialog){const r=dialog.getBoundingClientRect();if(event.clientX<r.left||event.clientX>r.right)closeDrawer(dialog);}});dialog.addEventListener('cancel',event=>{event.preventDefault();closeDrawer(dialog);});dialog.addEventListener('close',()=>{dialog.classList.remove('bazar-drawer-visible');document.documentElement.classList.remove('bazar-drawer-active');if(dialog.id==='bazar-menu')document.documentElement.classList.remove('bazar-menu-pushed');if(lastFocus)lastFocus.focus({preventScroll:true});});});
 document.querySelectorAll('[data-bazar-search]').forEach(button=>button.addEventListener('click',()=>{const search=document.getElementById('bazar-search');search.classList.add('is-open');document.getElementById('bazar-search-input').focus();}));
 document.querySelectorAll('[data-bazar-pages]').forEach(slider=>{
  const track=slider.querySelector('[data-bazar-track]'),win=slider.querySelector('.bazar-slider-window'),items=Array.from(track.children),dots=slider.querySelector('[data-bazar-dots]');let page=0,total=1,per=1,rows=1;
  function show(){page=Math.max(0,Math.min(page,total-1));track.style.transform='translateX(-'+(page*100)+'%)';items.forEach((item,i)=>{const active=Math.floor(i/(per*rows))===page;item.inert=!active;item.setAttribute('aria-hidden',String(!active));});slider.querySelectorAll('[data-bazar-prev]').forEach(b=>b.disabled=page===0);slider.querySelectorAll('[data-bazar-next]').forEach(b=>b.disabled=page===total-1);if(dots)dots.querySelectorAll('button').forEach((b,i)=>b.setAttribute('aria-current',String(page===i)));}
  function resize(){per=Number(getComputedStyle(slider).getPropertyValue('--bazar-per-page'))||5;rows=Number(getComputedStyle(slider).getPropertyValue('--bazar-rows'))||1;total=Math.max(1,Math.ceil(items.length/(per*rows)));if(slider.dataset.bazarPages==='flash')items.forEach((item,i)=>{const group=Math.floor(i/(per*rows)),slot=i%(per*rows);item.style.gridColumn=String(group*per+(slot%per)+1);item.style.gridRow=String(Math.floor(slot/per)+1);});if(dots){dots.replaceChildren();for(let i=0;i<total;i++){const b=document.createElement('button');b.type='button';b.dataset.bazarPage=i;b.setAttribute('aria-label','Show page '+(i+1));b.addEventListener('click',()=>{page=i;show();});dots.append(b);}}slider.querySelectorAll('.bazar-slider-controls').forEach(el=>el.hidden=total<=1);show();}
  slider.querySelectorAll('[data-bazar-prev]').forEach(b=>b.addEventListener('click',()=>{page--;show();}));slider.querySelectorAll('[data-bazar-next]').forEach(b=>b.addEventListener('click',()=>{page++;show();}));let startX=0,startY=0;win.addEventListener('touchstart',e=>{startX=e.touches[0].clientX;startY=e.touches[0].clientY;},{passive:true});win.addEventListener('touchend',e=>{const dx=e.changedTouches[0].clientX-startX,dy=e.changedTouches[0].clientY-startY;if(Math.abs(dx)>45&&Math.abs(dx)>Math.abs(dy)){page+=dx<0?1:-1;show();}},{passive:true});if(typeof ResizeObserver!=='undefined')new ResizeObserver(resize).observe(win);else window.addEventListener('resize',resize);resize();
 });
 document.querySelectorAll('[data-bazar-slider]').forEach(slider=>{const slides=slider.querySelectorAll('.bazar-banner-slide');slider.querySelectorAll('[data-bazar-slide]').forEach(button=>button.addEventListener('click',()=>{slides.forEach((slide,i)=>slide.hidden=i!==Number(button.dataset.bazarSlide));slider.querySelectorAll('[data-bazar-slide]').forEach(dot=>dot.setAttribute('aria-pressed',String(dot===button)));}));});
 if(window.jQuery)jQuery(document).ajaxSuccess(function(event,xhr,settings){if(/cart\/(addToCart|removeFromCart|updateQuantity)/.test(settings.url||'')){try{const data=JSON.parse(xhr.responseText);if(typeof data.cart_count!=='undefined')document.querySelectorAll('.bazar-cart-count').forEach(el=>el.textContent=data.cart_count);}catch(e){}if(cart.open)loadCart();}});
});
