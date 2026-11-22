/** Use numeric server prices; never guess from formatted currency strings. */
export function productDiscount(variants,minQty=1){
 const available=variants.filter(v=>Number(v.stock)>=minQty);
 const choices=available.length?available:variants;
 if(!choices.length)return null;
 const percentages=choices.map(v=>{
  const regular=Number(v.regular_price),sale=Number(v.price);
  if(!Number.isFinite(regular)||!Number.isFinite(sale)||regular<=0||sale<0)return null;
  return sale<regular?(regular-sale)*100/regular:0;
 });
 if(percentages.some(value=>value===null))return null;
 const maximum=Math.max(...percentages),minimum=Math.min(...percentages);
 if(maximum<=0)return null;
 const percent=Math.min(100,Math.floor(maximum+1e-7));
 return {percent,upTo:maximum-minimum>.01,label:percent?`${maximum-minimum>.01?'Up to ':''}${percent}% OFF`:'Special price'};
}
