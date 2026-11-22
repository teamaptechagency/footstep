export function allowedCommercePath(path,method){
 if(path.includes('..')||path.includes('\\')||path.includes('%')||path.startsWith('/'))return false;
 if(method==='GET')return /^storefront\/product\/\d+\/session$/.test(path)||/^checkout\/quick\/\d+$/.test(path)||path==='order-confirmed'||/^public\/(assets|uploads)\/[A-Za-z0-9_./-]+\.(css|js|png|jpg|jpeg|webp|gif|svg|ico|woff2?|ttf|eot)$/i.test(path);
 return method==='POST'&&(/^storefront\/product\/\d+\/(review|cart)$/.test(path)||['checkout/direct/quote','checkout/direct','checkout/draft'].includes(path));
}
export function replaceLaravelOrigin(text,origins){for(const origin of origins){if(origin){const base=origin.replace(/\/$/,'');text=text.split(base+'/order-confirmed').join('/order-confirmed');text=text.split(base+'/').join('/commerce/');}}return text;}
export function selectInitialVariant(html,variant,quantity){
 const v=JSON.stringify(String(variant||'').slice(0,100)).replace(/</g,'\\u003c');
 const q=Math.max(1,Math.min(99,Number.parseInt(quantity,10)||1));
 const script=`<script>(function(){var source=document.getElementById('option-choice-form'),select=document.getElementById('direct-stock');if(!source||!select)return;var option=Array.from(select.options).find(function(o){return !o.disabled&&o.dataset.variant===${v};});var changed=false;if(option){source.querySelector('[name="stock_id"]').value=option.value;changed=true;}var qty=source.querySelector('[name="quantity"]');if(qty&&${q}>Number(qty.value)){qty.value=${q};changed=true;}if(changed)source.dataset.initialQuote='null';})();</script>`;
 return html.replace('</body>',script+'</body>');
}
