<style>
.fs-size-picker{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 8px}
.fs-size-picker button{min-width:48px;min-height:42px;padding:8px 12px;border:1px solid #ccdcd7;border-radius:8px;background:#fff;color:#23443b;font-weight:700;cursor:pointer}
.fs-size-picker button[aria-pressed="true"]{background:#087f56;border-color:#087f56;color:#fff;box-shadow:0 2px 5px #087f5620}
.fs-size-picker button:focus-visible{outline:3px solid #e8b028;outline-offset:2px}
.fs-size-picker button:disabled{opacity:.5;cursor:not-allowed}
.fs-size-native,.fs-size-native-wrapper{display:none!important}
.fs-size-help{display:block;color:#64766f;font-size:12px;line-height:1.6}
</style>
<script>
(function(){
 function setup(){
  document.querySelectorAll('#customer_choice_options select.attribute_choice').forEach(function(select){
   var existingWrapper=select.closest('.bootstrap-select');
   if(select.dataset.sizeChips){if(existingWrapper)existingWrapper.classList.add('fs-size-native-wrapper');return;}
   var row=select.closest('.form-group');
   var title=row&&row.querySelector('input[name="choice[]"]');
   var options=Array.from(select.options);
   var numericSizes=options.length>0&&options.every(function(o){return /^\d{2}(\.5)?$/.test(o.value)&&Number(o.value)>=20&&Number(o.value)<=60});
   if(!/size|সাইজ/i.test(title?title.value:'')&&!numericSizes)return;
   select.dataset.sizeChips='1';
   var wrapper=select.closest('.bootstrap-select');
   select.classList.add('fs-size-native');
   if(wrapper)wrapper.classList.add('fs-size-native-wrapper');
   var host=wrapper||select;
   var chips=document.createElement('div');chips.className='fs-size-picker';chips.setAttribute('role','group');chips.setAttribute('aria-label','Available product sizes');
   var help=document.createElement('small');help.className='fs-size-help';
   options.forEach(function(option){
    var button=document.createElement('button');button.type='button';
    function sync(){button.textContent=(option.selected?'✓ ':'')+option.text.trim();button.setAttribute('aria-pressed',String(option.selected));button.disabled=select.disabled||option.disabled}
    button.addEventListener('click',function(){option.selected=!option.selected;window.jQuery(select).trigger('change');syncAll()});
    button._syncSize=sync;chips.appendChild(button);
   });
   function syncAll(){Array.from(chips.children).forEach(function(b){b._syncSize()});var count=options.filter(function(o){return o.selected}).length;help.textContent=count+' selected / সাইজে ক্লিক করে নির্বাচন বা বাদ দিন।'}
   window.jQuery(select).on('change.sizeChips',syncAll);
   host.insertAdjacentElement('afterend',chips);chips.insertAdjacentElement('afterend',help);syncAll();
  });
 }
 if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',start);else start();
 function start(){setup();var root=document.getElementById('customer_choice_options');if(root)new MutationObserver(setup).observe(root,{childList:true,subtree:true})}
})();
</script>
