(function () {
    'use strict';
    function init() {
        var root = document.getElementById('footstep-friend-share');
        if (!root) return;
        var status = document.getElementById('footstep-share-status');
        var fallback = document.getElementById('footstep-share-fallback');
        var input = document.getElementById('footstep-share-link');
        function payload() {
            var chosen = document.querySelector('#option-choice-form #chosen_price');
            var price = chosen && chosen.textContent.trim() || root.dataset.price;
            return {title:root.dataset.title,text:root.dataset.title + ' — ' + price,url:root.dataset.url};
        }
        root.querySelector('.footstep-share-whatsapp').addEventListener('click', function () {
            var data=payload(); this.href='https://wa.me/?text='+encodeURIComponent(data.text+'\n'+data.url);
        });
        root.querySelectorAll('[data-native-share]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (!navigator.share) {
                    status.textContent='এই browser-এ app sharing নেই। Product link দিয়ে বন্ধুকে share করুন।';
                    fallback.hidden=false; input.focus(); input.select(); return;
                }
                status.textContent='ফোনের share menu থেকে '+button.dataset.nativeShare+' বা আপনার পছন্দের app বেছে নিন।';
                navigator.share(payload()).then(function () { status.textContent='Share menu খোলা হয়েছে।'; },function (error) {
                    if (error.name==='AbortError') { status.textContent=''; return; }
                    status.textContent='এই browser থেকে share menu খোলা যায়নি। Product link ব্যবহার করুন।'; fallback.hidden=false;
                });
            });
        });
        document.getElementById('footstep-copy-share-link').addEventListener('click', function () {
            function copyFallback() { input.focus();input.select();try{return document.execCommand('copy');}catch(error){return false;} }
            function done(ok) { status.textContent=ok?'Product link copied. বন্ধুর chat-এ পাঠাতে পারবেন।':'Link select করা আছে। Copy করে বন্ধুকে পাঠান।'; }
            if(navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(input.value).then(function(){done(true);},function(){done(copyFallback());}); else done(copyFallback());
        });
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',init);else init();
}());
