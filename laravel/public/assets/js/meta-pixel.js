window.FootstepMetaMoney=function(data,fallback,purchase){data=Object.assign({},data||{});if(!purchase&&!Object.prototype.hasOwnProperty.call(data,'value')&&!Object.prototype.hasOwnProperty.call(data,'currency'))return data;if(purchase&&!Object.prototype.hasOwnProperty.call(data,'value'))return null;if(Object.prototype.hasOwnProperty.call(data,'value')){if((typeof data.value!=='number'&&typeof data.value!=='string')||String(data.value).trim()===''||!Number.isFinite(Number(data.value))||Number(data.value)<0)return null;data.value=Math.round(Number(data.value)*100)/100;}let currency=typeof data.currency==='string'?data.currency.trim().toUpperCase():'';const backup=typeof fallback==='string'?fallback.trim().toUpperCase():'';data.currency=/^[A-Z]{3}$/.test(currency)?currency:(/^[A-Z]{3}$/.test(backup)?backup:'BDT');return data;};
(function(window,document){
    'use strict';
    var config=window.FootstepMetaConfig||{},pending=(window.FootstepMetaEvents||[]).filter(Boolean),started=false,sent={};
    if(!config.enabled)return;
    function privacyBlocked(){return navigator.globalPrivacyControl||navigator.doNotTrack==='1';}
    function choice(){if(Object.prototype.hasOwnProperty.call(config,'consent'))return config.consent;var m=document.cookie.match(/(?:^|; )footstep_marketing=([^;]*)/);return m?decodeURIComponent(m[1]):'';}
    function allowed(){return config.enabled&&!privacyBlocked()&&choice()!=='denied'&&(!config.require_consent||choice()==='granted');}
    function post(url,data){return fetch(url,{method:'POST',credentials:'same-origin',keepalive:true,headers:{'Content-Type':'application/json','X-CSRF-TOKEN':config.csrf,'Accept':'application/json'},body:JSON.stringify(data)});}
    function readOnce(key){try{return localStorage.getItem(key)==='1';}catch(e){return false;}}
    function markOnce(key){try{localStorage.setItem(key,'1');}catch(e){}}
    function attribution(){
        if(!allowed())return;
        var click=new URL(window.location.href).searchParams.get('fbclid');
        if(click&&/^[A-Za-z0-9_-]{1,200}$/.test(click))document.cookie='_fbc='+encodeURIComponent('fb.1.'+Date.now()+'.'+click)+'; Path=/; Max-Age=7776000; SameSite=Lax'+(location.protocol==='https:'?'; Secure':'');
    }
    function start(){
        if(!allowed())return;
        attribution();
        if(!started){
            started=true;
            if(config.browser){
                if(!window.fbq){var n=window.fbq=function(){n.callMethod?n.callMethod.apply(n,arguments):n.queue.push(arguments);};window._fbq=n;n.push=n;n.loaded=true;n.version='2.0';n.queue=[];var script=document.createElement('script');script.async=true;script.src='https://connect.facebook.net/en_US/fbevents.js';document.head.appendChild(script);}
                // Automatic form matching is disabled; only explicit, consented hashes are used.
                fbq('set','autoConfig',false,config.id);
                var match={};pending.some(function(event){if(event.match){Object.keys(event.match).forEach(function(k){match[k]=event.match[k][0];});}return Object.keys(match).length>0;});
                fbq('init',config.id,match);
            }
        }
        var events=pending;pending=[];events.forEach(emit);
    }
    function emit(event){
        if(!event||!config.events[event.name])return;
        var monetary=window.FootstepMetaMoney(event.data,config.currency,event.name==='Purchase');if(!monetary)return;event=Object.assign({},event,{data:monetary});
        if(!allowed()){if(choice()!=='denied')pending.push(event);return;}
        if(!started){pending.push(event);start();return;}
        var key='footstep-meta-v2:'+config.id+':'+event.name+':'+event.id;
        if(sent[key])return;sent[key]=true;
        if(config.capi&&event.ticket)post(config.endpoint,{ticket:event.ticket}).catch(function(){});
        if(event.stable&&readOnce(key))return;
        if(config.browser&&window.fbq)fbq('trackSingle',config.id,event.name,event.data||{},{eventID:event.id});
        if(event.stable)markOnce(key);
    }
    window.FootstepMeta={emit:emit,preferences:function(){showConsent();},update:function(next,events){var previous=choice();config=next;if(choice()==='denied'){pending=[];if(window.fbq)window.fbq('consent','revoke');['_fbp','_fbc'].forEach(function(name){document.cookie=name+'=; Path=/; Max-Age=0; SameSite=Lax';});return;}if(choice()==='granted'&&previous!=='granted'&&window.fbq)window.fbq('consent','grant');pending=(events||[]).filter(Boolean);start();}};
    function showConsent(){
        var old=document.getElementById('meta-consent');if(old)old.remove();
        var panel=document.createElement('div');panel.id='meta-consent';panel.setAttribute('role','dialog');panel.setAttribute('aria-label','Meta marketing privacy');
        panel.style.cssText='position:fixed;z-index:99999;left:16px;right:16px;bottom:78px;max-width:520px;padding:18px;border-radius:12px;background:white;color:#1f2937;box-shadow:0 4px 30px #0003;border:1px solid #ddd';
        var text=document.createElement('p');text.textContent='Allow Meta marketing cookies to help us measure ads? You can change this choice anytime.';panel.appendChild(text);
        [['Allow','granted'],['Not now','denied']].forEach(function(option){var button=document.createElement('button');button.type='button';button.className='btn btn-sm '+(option[1]==='granted'?'btn-primary':'btn-light');button.style.marginRight='8px';button.textContent=option[0];button.onclick=function(){post(config.consent_endpoint,{choice:option[1]}).then(function(response){if(!response.ok)throw Error('Consent save failed');config.consent=option[1];panel.remove();if(option[1]==='granted'){if(window.fbq)fbq('consent','grant');start();}else{pending=[];if(window.fbq)fbq('consent','revoke');['_fbp','_fbc'].forEach(function(name){document.cookie=name+'=; Path=/; Max-Age=0; SameSite=Lax';});}}).catch(function(){text.textContent='Your choice could not be saved. Please retry.';});};panel.appendChild(button);});
        document.body.appendChild(panel);
    }
    window.addEventListener('footstep:meta',function(event){emit(event.detail);});
    function ready(){
        if(privacyBlocked())return;
        // No automatic cookie message; explicit privacy preferences still control opt-in.
        start();
        if(window.jQuery){jQuery(document).ajaxSuccess(function(event,xhr){var body=xhr.responseJSON;if(body&&body.status!==0&&body.meta_event)emit(body.meta_event);var header=xhr.getResponseHeader('X-Meta-Event');if(header){try{emit(JSON.parse(atob(header)));}catch(e){}}});}
        document.addEventListener('click',function(event){var link=event.target.closest('[data-meta-contact],a[href]');if(!link)return;var href=link.getAttribute('href')||'';if(link.hasAttribute('data-meta-contact')||/^(https?:\/\/)?(wa\.me|api\.whatsapp\.com|m\.me)\//i.test(href)){emit(config.contact_event);}});
        function payment(event){var form=event.target;if(form.matches('form[data-meta-payment]')&&(!form.checkValidity||form.checkValidity()))emit(config.payment_event);}
        document.addEventListener('submit',payment,true);
        if(window.jQuery)jQuery(document).on('submit','form[data-meta-payment]',payment);
    }
    if(document.readyState==='loading')document.addEventListener('DOMContentLoaded',ready);else ready();
})(window,document);
