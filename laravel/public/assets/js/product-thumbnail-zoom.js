(function () {
 'use strict';
 if (window.footstepMainGalleryZoom) return;
 window.footstepMainGalleryZoom = true;
 const initialized = new WeakSet();
 const clamp = (value, min, max) => Math.max(min, Math.min(max, value));
 function setup(frame) {
  if (initialized.has(frame)) return;
  const image = frame.querySelector('img:not(.zoomImg)');
  if (!image) return;
  initialized.add(frame);
  frame.classList.add('main-gallery-zoom');
  frame.querySelectorAll('.main-gallery-controls').forEach(el => el.remove());
  frame.tabIndex = 0;
  frame.setAttribute('aria-label', 'Product image. Pinch or use mouse wheel to zoom; drag to move. Double click or press 0 to reset.');
  let scale = 1, x = 0, y = 0, drag = null, pinch = null;
  function paint() {
   const r = frame.getBoundingClientRect();
   x = clamp(x, -r.width*(scale-1)/2, r.width*(scale-1)/2);
   y = clamp(y, -r.height*(scale-1)/2, r.height*(scale-1)/2);
   image.style.setProperty('transform', 'translate('+x+'px,'+y+'px) scale('+scale+')', 'important');
   image.style.setProperty('transform-origin', 'center', 'important');
   frame.classList.toggle('is-zoomed', scale > 1);
  }
  function zoom(value, clientX, clientY) {
   const next=clamp(value,1,3),r=frame.getBoundingClientRect();
   const px=(clientX===undefined?r.left+r.width/2:clientX)-r.left-r.width/2;
   const py=(clientY===undefined?r.top+r.height/2:clientY)-r.top-r.height/2;
   x=px-(px-x)*next/scale; y=py-(py-y)*next/scale; scale=next;
   if(scale===1)x=y=0;
   paint();
  }
  image.draggable = false;
  frame.addEventListener('wheel', e => {
   if(!Number.isFinite(e.deltaY)||e.deltaY===0)return;
   const delta=e.deltaY*(e.deltaMode===1?16:e.deltaMode===2?frame.clientHeight:1);
   const next=clamp(scale*Math.exp(-clamp(delta,-150,150)*.003),1,3);
   if(next===scale)return; // At a boundary, keep normal page scrolling available.
   e.preventDefault(); e.stopPropagation(); zoom(next,e.clientX,e.clientY);
  },{passive:false});
  frame.addEventListener('dblclick', e => { e.preventDefault(); e.stopPropagation(); zoom(scale>1?1:2,e.clientX,e.clientY); });
  frame.addEventListener('keydown', e => {
   if(!['+','=','-','0','Escape'].includes(e.key))return;
   if(e.key==='Escape'&&scale===1)return;
   e.preventDefault(); zoom(e.key==='0'||e.key==='Escape'?1:scale+(e.key==='-'?-.25:.25));
  });
  frame.addEventListener('pointerdown', e => {
   if(e.pointerType!=='mouse'||scale<=1||e.button!==0)return;
   e.preventDefault();e.stopPropagation();drag={id:e.pointerId,startX:e.clientX,startY:e.clientY,x:x,y:y};
   frame.setPointerCapture(e.pointerId);
  });
  frame.addEventListener('pointermove', e => {
   if(e.pointerType!=='mouse'||scale<=1)return;
   if(drag&&drag.id===e.pointerId){e.preventDefault();e.stopPropagation();x=drag.x+e.clientX-drag.startX;y=drag.y+e.clientY-drag.startY;paint();}
   else if(e.buttons===0){const r=frame.getBoundingClientRect();x=(.5-(e.clientX-r.left)/r.width)*r.width*(scale-1);y=(.5-(e.clientY-r.top)/r.height)*r.height*(scale-1);paint();}
  });
  ['pointerup','pointercancel','lostpointercapture'].forEach(type=>frame.addEventListener(type,()=>{drag=null;}));
  const distance=t=>Math.hypot(t[0].clientX-t[1].clientX,t[0].clientY-t[1].clientY);
  const middle=t=>({x:(t[0].clientX+t[1].clientX)/2,y:(t[0].clientY+t[1].clientY)/2});
  frame.addEventListener('touchstart', e => {
   const t=e.targetTouches;
   if(t.length>=2){e.preventDefault();e.stopPropagation();pinch={distance:Math.max(1,distance(t)),scale:scale,center:middle(t),x:x,y:y};drag=null;}
   else if(t.length===1&&scale>1){e.preventDefault();e.stopPropagation();drag={startX:t[0].clientX,startY:t[0].clientY,x:x,y:y};}
  },{passive:false});
  frame.addEventListener('touchmove', e => {
   const t=e.targetTouches;
   if(t.length>=2&&pinch){
    e.preventDefault();e.stopPropagation();const center=middle(t),r=frame.getBoundingClientRect();
    const next=clamp(pinch.scale*distance(t)/pinch.distance,1,3);
    const px=pinch.center.x-r.left-r.width/2,py=pinch.center.y-r.top-r.height/2;
    x=center.x-r.left-r.width/2-(px-pinch.x)*next/pinch.scale;
    y=center.y-r.top-r.height/2-(py-pinch.y)*next/pinch.scale;
    scale=next;if(scale===1)x=y=0;paint();
   }else if(t.length===1&&drag&&scale>1){e.preventDefault();e.stopPropagation();x=drag.x+t[0].clientX-drag.startX;y=drag.y+t[0].clientY-drag.startY;paint();}
  },{passive:false});
  function touchEnd(e){
   pinch=null;drag=null;
   if(e.targetTouches.length===1&&scale>1){const t=e.targetTouches[0];drag={startX:t.clientX,startY:t.clientY,x:x,y:y};}
  }
  frame.addEventListener('touchend',touchEnd);frame.addEventListener('touchcancel',()=>{pinch=drag=null;});
  paint();
 }
 function scan(node){if(node.nodeType!==1&&node.nodeType!==9)return;if(node.matches&&node.matches('.product-gallery .carousel-box'))setup(node);node.querySelectorAll('.product-gallery .carousel-box').forEach(setup);}
 function init(){scan(document);new MutationObserver(records=>records.forEach(r=>r.addedNodes.forEach(scan))).observe(document.body,{childList:true,subtree:true});}
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
