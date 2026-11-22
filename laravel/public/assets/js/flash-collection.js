(function(){'use strict';
const root=document.getElementById('flash-collection');if(!root)return;
const heading=root.querySelector('#sale-title'),header=document.querySelector('.logo-bar-area,.bazar-header-main,.studio-header-main'),clock=root.querySelector('.fsale-clock'),info=root.querySelector('.fsale-info');
let visitorClock=null,visitorDeadline=0;
if(heading&&header&&clock){
 const group=document.createElement('div');group.className='fsale-header-title';
 const title=document.createElement('span');title.className='fsale-header-name';title.textContent=heading.textContent;
 group.append(title);header.classList.add('fsale-header-host');header.append(group);root.classList.add('fsale-title-in-header');
 const compact=root.dataset.compactHeader==='1';
 if(compact){
  header.classList.add('fsale-compact-header');title.textContent='For Today';
  const now=Date.now(),key='footstep:flash:first-visit:v1:'+window.location.pathname;
  let firstVisit=now;
  try{
   const saved=Number(window.localStorage.getItem(key));
   if(Number.isFinite(saved)&&saved>0&&saved<=now)firstVisit=saved;
   else window.localStorage.setItem(key,String(now));
  }catch(error){/* Private storage may be unavailable; retain this visit in memory. */}
  visitorDeadline=firstVisit+86400000;
  visitorClock=document.createElement('span');visitorClock.className='fsale-visitor-clock';
  visitorClock.setAttribute('role','timer');visitorClock.setAttribute('aria-label','24 hours from your first visit');
  visitorClock.title='আপনার এই browser-এ প্রথম ভিজিট থেকে ২৪ ঘণ্টা';
  group.append(visitorClock);
 }
 const mobile=window.matchMedia('(max-width:767px)');
 function placeClock(){if(mobile.matches&&!compact)group.append(clock);else info.append(clock);}
 placeClock();if(mobile.addEventListener)mobile.addEventListener('change',placeClock);else mobile.addListener(placeClock);
}
function tick(){
 if(visitorClock){const left=Math.max(0,Math.ceil((visitorDeadline-Date.now())/1000));visitorClock.textContent=[Math.floor(left/3600),Math.floor(left/60)%60,left%60].map(value=>String(value).padStart(2,'0')).join(' : ');}
 const remaining=Math.max(0,Math.floor(Number(root.dataset.end)-Date.now()/1000));
 // Show a <=24 hour cycle while preserving the real offer end and active state.
 const seconds=remaining>86400?((remaining-1)%86400)+1:remaining;
 const values={hours:Math.floor(seconds/3600),minutes:Math.floor(seconds/60)%60,seconds:seconds%60};
 Object.keys(values).forEach(name=>{const el=root.querySelector('[data-unit="'+name+'"]')||clock.querySelector('[data-unit="'+name+'"]');if(el)el.textContent=String(values[name]).padStart(2,'0');});
 if(remaining===0){if(!visitorClock)clearInterval(timer);const state=root.querySelector('[data-sale-state]');if(state)state.textContent='Offer time ended — refresh for current availability.';}
}
let timer=setInterval(tick,1000);tick();
}());
