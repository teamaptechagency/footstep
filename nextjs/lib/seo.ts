import type {Metadata} from 'next';
import {image,Product} from './catalog';
export const siteUrl='https://footsteponline.com';
export const indexable=process.env.FOOTSTEP_SEO_INDEXABLE==='1';
export function cleanText(input:string|undefined,max=165){return (input||'').replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi,'').replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi,'').replace(/<[^>]*>/g,' ').replace(/&nbsp;/g,' ').replace(/&amp;/g,'&').replace(/\s+/g,' ').trim().slice(0,max);}
export function pageSeo(title:string,description:string,path:string,allow=true):Metadata{
 const url=new URL(path,siteUrl).href;
 return {title,description,alternates:{canonical:url},robots:{index:indexable&&allow,follow:true},openGraph:{type:'website',url,title,description,siteName:'Footstep Online',locale:'en_BD'},twitter:{card:'summary_large_image',title,description}};
}
export function productPath(p:Product){return `/product/${encodeURIComponent(p.slug||String(p.id))}`;}
export function productSeo(p:Product):Metadata{const title=cleanText(p.meta_title||p.name,90);const description=cleanText(p.meta_description||p.description)||`${cleanText(p.name,100)}: check current price, product photos and available sizes at Footstep Online, Bangladesh.`;return {...pageSeo(title,description,productPath(p)),openGraph:{type:'website',url:siteUrl+productPath(p),title,description,images:[{url:image(p.thumbnail_image),alt:p.name}]},twitter:{card:'summary_large_image',title,description,images:[image(p.thumbnail_image)]}};}
export function productSchema(p:Product){
 const variants=(p.variants||[]).filter(v=>Number.isFinite(v.price)&&v.price>=0),stock=variants.filter(v=>v.stock>=Math.max(1,p.min_qty||1));
 const choices=stock.length?stock:variants;const prices=choices.map(v=>v.price);const low=prices.length?Math.min(...prices):null,high=prices.length?Math.max(...prices):null;
 const url=siteUrl+productPath(p);const offers=low===null?undefined:low===high?{'@type':'Offer',url,priceCurrency:'BDT',price:low,availability:stock.length?'https://schema.org/InStock':'https://schema.org/OutOfStock',itemCondition:'https://schema.org/NewCondition'}:{'@type':'AggregateOffer',url,priceCurrency:'BDT',lowPrice:low,highPrice:high,offerCount:choices.length,availability:stock.length?'https://schema.org/InStock':'https://schema.org/OutOfStock'};
 return {'@context':'https://schema.org','@type':'Product','@id':url+'#product',url,name:p.name,description:cleanText(p.description,1000),image:[...new Set([image(p.thumbnail_image),...(p.photos||[]).map(v=>image(v.path))])].filter(Boolean),sku:String(p.id),...(p.brand?.name?{brand:{'@type':'Brand',name:p.brand.name}}:{}),...(offers?{offers}:{})};
}
export function breadcrumbs(items:{name:string;path:string}[]){return {'@context':'https://schema.org','@type':'BreadcrumbList',itemListElement:items.map((item,i)=>({'@type':'ListItem',position:i+1,name:item.name,item:new URL(item.path,siteUrl).href}))};}
export function jsonLd(value:unknown){return JSON.stringify(value).replace(/</g,'\\u003c');}
