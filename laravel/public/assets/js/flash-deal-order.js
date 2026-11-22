(function () {
    'use strict';
    function init() {
        var config=window.FootstepFlashOrder, $=window.jQuery;
        if(!config||!$)return;
        var box=document.getElementById('discount_table'), select=document.getElementById('products');
        if(!box||!select)return;
        var order=(config.initialOrder||[]).map(String), sequence=0, active=null, saved={};
        function rows(){return Array.from(box.querySelectorAll('tbody tr[data-product-id]'));}
        function sync(){order=rows().map(function(row,index){row.querySelector('.flash-position').textContent=index+1;return row.dataset.productId;});}
        function remember(){rows().forEach(function(row){var id=row.dataset.productId;saved[id]={discount:row.querySelector('[name="discount_'+id+'"]').value,type:row.querySelector('[name="discount_type_'+id+'"]').value};});}
        function load(){
            remember();var selected=($(select).val()||[]).map(String);
            order=order.filter(function(id){return selected.indexOf(id)!==-1;});selected.forEach(function(id){if(order.indexOf(id)===-1)order.push(id);});
            var version=++sequence;delete box.dataset.loadFailed;
            if(!order.length){box.innerHTML='<p class="text-muted py-3" role="status">কোনো product বাছাই করা নেই। উপরের Products থেকে যোগ করুন।</p>';box.removeAttribute('aria-busy');return;}
            box.setAttribute('aria-busy','true');
            $.ajax({url:config.endpoint,method:'POST',data:{_token:config.csrf,product_ids:order,flash_deal_id:config.dealId},success:function(html){
                if(version!==sequence)return;remember();box.innerHTML=html;
                rows().forEach(function(row){var id=row.dataset.productId;if(saved[id]){row.querySelector('[name="discount_'+id+'"]').value=saved[id].discount;row.querySelector('[name="discount_type_'+id+'"]').value=saved[id].type;}});
                if($.fn.selectpicker)$(box).find('.aiz-selectpicker').selectpicker();sync();box.removeAttribute('aria-busy');delete box.dataset.loadFailed;
            },error:function(){if(version!==sequence)return;box.removeAttribute('aria-busy');box.dataset.loadFailed='1';window.alert('Could not load selected products. Retry your selection before saving.');}});
        }
        box.addEventListener('pointerdown',function(event){var handle=event.target.closest('.flash-drag-handle');if(!handle||event.button!==0)return;event.preventDefault();active=handle.closest('tr');active.classList.add('table-info');});
        document.addEventListener('pointermove',function(event){if(!active)return;event.preventDefault();var target=document.elementFromPoint(event.clientX,event.clientY);var row=target&&target.closest('tr[data-product-id]');if(!row||row===active||!box.contains(row))return;var bounds=row.getBoundingClientRect();row.parentNode.insertBefore(active,event.clientY<bounds.top+bounds.height/2?row:row.nextSibling);sync();},{passive:false});
        function finish(){if(active){active.classList.remove('table-info');active=null;sync();}}
        document.addEventListener('pointerup',finish);document.addEventListener('pointercancel',finish);
        box.addEventListener('click',function(event){
            var remove=event.target.closest('[data-flash-remove]');
            if(remove){
                event.preventDefault();var removed=remove.closest('tr[data-product-id]');if(!removed)return;
                remember();var id=removed.dataset.productId;++sequence;
                Array.from(select.options).forEach(function(option){if(String(option.value)===id)option.selected=false;});
                removed.remove();sync();
                if($.fn.selectpicker)$(select).selectpicker('refresh');
                load();return;
            }
            var button=event.target.closest('[data-flash-move]');if(!button)return;var row=button.closest('tr'),other=button.dataset.flashMove==='up'?row.previousElementSibling:row.nextElementSibling;if(!other)return;row.parentNode.insertBefore(row,button.dataset.flashMove==='up'?other:other.nextSibling);sync();button.focus();
        });
        select.addEventListener('change',load);
        // Bootstrap Select emits a jQuery change event; coalesce it with native changes.
        $(select).off('change.flashOrder').on('changed.bs.select.flashOrder',load);
        select.form.addEventListener('submit',function(event){var selected=($(select).val()||[]).map(String);sync();if(box.getAttribute('aria-busy')==='true'||box.dataset.loadFailed==='1'||order.length!==selected.length||order.some(function(id){return selected.indexOf(id)===-1;})){event.preventDefault();window.alert('Wait for all selected products to load before saving.');}});
        load();
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
