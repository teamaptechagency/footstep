(function(){
    'use strict';
    var root=document.getElementById('review-manager');if(!root)return;
    var all=root.querySelector('#rv-select-all'),rows=Array.prototype.slice.call(root.querySelectorAll('.rv-row-select')),count=root.querySelector('#rv-selected'),bulk=root.querySelector('#review-bulk-delete'),button=root.querySelector('#rv-bulk-button');
    function selected(){return rows.filter(function(row){return row.checked;}).length;}
    function update(){var n=selected();if(count)count.textContent=n+' selected';if(button)button.disabled=n===0;if(all){all.checked=rows.length>0&&n===rows.length;all.indeterminate=n>0&&n<rows.length;all.disabled=rows.length===0;}}
    if(all)all.addEventListener('change',function(){rows.forEach(function(row){row.checked=all.checked;});update();});
    rows.forEach(function(row){row.addEventListener('change',update);});update();
    function confirmDelete(){return window.confirm('এই review স্থায়ীভাবে মুছবেন? Deleted reviews cannot be recovered.');}
    if(bulk)bulk.addEventListener('submit',function(event){if(!selected()||!confirmDelete())event.preventDefault();});
    root.querySelectorAll('.rv-single-delete').forEach(function(form){form.addEventListener('submit',function(event){if(!confirmDelete())event.preventDefault();});});
    function message(text,ok){var box=root.querySelector('#rv-message');if(!box)return;box.textContent=text;box.className='alert '+(ok?'alert-success':'alert-danger');}
    root.querySelectorAll('.rv-publish-toggle').forEach(function(toggle){toggle.addEventListener('change',function(){
        var desired=toggle.checked,token=document.querySelector('meta[name="csrf-token"]');toggle.disabled=true;
        var body=new URLSearchParams();body.set('_token',token?token.content:'');body.set('id',toggle.value);body.set('status',desired?'1':'0');
        fetch(toggle.dataset.url,{method:'POST',credentials:'same-origin',headers:{'Accept':'application/json','Content-Type':'application/x-www-form-urlencoded'},body:body.toString()})
        .then(function(response){if(!response.ok)throw new Error('request');return response.text();})
        .then(function(text){if(text.trim()!=='1')throw new Error('rejected');toggle.closest('.rv-publish').querySelector('.rv-status-text').textContent=desired?'Published':'Draft';message('Review status saved / অবস্থা সংরক্ষণ হয়েছে।',true);})
        .catch(function(){toggle.checked=!desired;message('Status save হয়নি। নমুনা হলে আগে edit করুন; অন্যথায় আবার চেষ্টা করুন।',false);})
        .then(function(){toggle.disabled=false;});
    });});
})();
