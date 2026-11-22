(function(){
 'use strict';
 const form=document.getElementById('option-choice-form'),data=document.getElementById('product-stock-data');
 if(!form||!data)return;
 const radios=Array.from(form.querySelectorAll('input[type="radio"]'));
 const names=Array.from(new Set(radios.map(r=>r.name)));
 if(!names.length)return;
 const groups=names.map(n=>radios.filter(r=>r.name===n));
 const ordered=groups.filter(g=>g[0].name==='color').concat(groups.filter(g=>g[0].name!=='color'));
 const minimum=Number(form.querySelector('[name="quantity"]')?.min||1);
 function matches(stock,selection){return stock.variant===selection.map(r=>r.name==='color'?r.value:r.value.replace(/ /g,'')).join('-');}
 const stocks=JSON.parse(data.textContent).filter(s=>s.qty>=minimum);
 const combinations=[];
 function expand(index,items){if(index===ordered.length){if(stocks.some(s=>matches(s,items)))combinations.push(items);return;}ordered[index].forEach(r=>expand(index+1,items.concat(r)));}
 expand(0,[]);
 function update(initial){
  let chosen=combinations.find(c=>c.every(r=>r.checked));
  if(!chosen){chosen=combinations[0];radios.forEach(r=>r.checked=!!chosen&&chosen.includes(r));}
  radios.forEach(r=>{
   const available=combinations.some(c=>c.includes(r)&&c.every(other=>other.name===r.name||other.checked));
   r.disabled=!available;
   const label=r.closest('label');
   if(label){label.classList.toggle('stock-unavailable',!available);label.title=available?'':'Unavailable / স্টক নেই';}
  });
  if(!chosen){form.querySelectorAll('.buy-now,.add-to-cart,[data-direct-checkout]').forEach(b=>b.disabled=true);}
 }
 form.addEventListener('change',function(e){if(radios.includes(e.target))update(false);},true);
 update(true);
}());
