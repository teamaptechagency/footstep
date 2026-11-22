(function(){
    'use strict';
    var menu=document.getElementById('main-menu');if(!menu)return;
    // Reorder the existing authorized DOM nodes, retaining all server-side role checks.
    var groups=[
        {title:'Overview',paths:['/admin','/admin/analytics','/admin/performance-logs']},
        {title:'Store management',paths:['/admin/products/admin','/admin/products/create','/admin/products','/admin/all_orders','/admin/customers','/admin/uploaded-files','/admin/media-center','/admin/reports']},
        {title:'Growth & website',paths:['/admin/platform/meta','/admin/flash_deals','/admin/blogs','/admin/website/header']},
        {title:'Tools & settings',paths:['/admin/image-optimization','/admin/platform/cache','/admin/store-operations/protection','/admin/settings','/admin/staffs','/admin/platform/releases']}
    ];
    function topItem(link){var item=link.closest('li');while(item&&item.parentElement!==menu){item=item.parentElement.closest('li');}return item;}
    var ordered=[],used=new Set();
    groups.forEach(function(group){
        var selected=[];
        group.paths.forEach(function(path){
            var link=Array.prototype.find.call(menu.querySelectorAll('a[href]'),function(a){try{return new URL(a.href,location.href).pathname.replace(/\/$/,'')===path;}catch(e){return false;}});
            var item=link?topItem(link):null;
            if(item&&!used.has(item)){used.add(item);selected.push(item);}
        });
        if(selected.length){var caption=document.createElement('li');caption.className='footstep-menu-section';caption.setAttribute('role','presentation');caption.textContent=group.title;ordered.push(caption);selected.forEach(function(item){ordered.push(item);});}
    });
    // Retain any legitimate extension menus that are not in the ordering map.
    Array.prototype.slice.call(menu.children).forEach(function(item){if(!used.has(item))ordered.push(item);});
    ordered.forEach(function(item){menu.appendChild(item);});
})();
