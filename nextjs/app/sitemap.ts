import type {MetadataRoute} from 'next';
import {origin} from '@/lib/catalog';
import {siteUrl} from '@/lib/seo';
export const revalidate=300;
export default async function sitemap():Promise<MetadataRoute.Sitemap>{
 const response=await fetch(`${origin}/api/storefront/v3/seo-index`,{next:{revalidate:300},signal:AbortSignal.timeout(15000)});
 if(!response.ok)throw new Error('Sitemap catalog unavailable');
 const data=await response.json() as {products:{slug:string;updated_at?:string}[];brands:{slug:string}[];categories:{slug:string}[]};
 const fixed=['/','/shop','/categories','/brands','/size-guide','/buying-guide','/returnpolicy'];
 return [...fixed.map(path=>({url:siteUrl+path})),...data.products.map(p=>({url:siteUrl+'/product/'+encodeURIComponent(p.slug),...(p.updated_at?{lastModified:new Date(p.updated_at)}:{})})),...data.brands.map(b=>({url:siteUrl+'/brand/'+encodeURIComponent(b.slug)})),...data.categories.map(c=>({url:siteUrl+'/category/'+encodeURIComponent(c.slug)}))];
}
