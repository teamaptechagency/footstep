import 'server-only';
import { mediaUrl } from './urls.mjs';
import {cache} from 'react';
export const origin = (process.env.LARAVEL_ORIGIN || 'http://127.0.0.1:8000').replace(/\/$/, '');
export const browserOrigin = (process.env.NEXT_PUBLIC_LARAVEL_ORIGIN || 'http://127.0.0.1:8000').replace(/\/$/, '');
export type ProductReview={id:number;name:string;rating:number;comment:string;date:string;imported?:boolean};
export type ProductAddon={id:number;name:string;description?:string;regular_price:number;price:number};
export type Product = { meta_title?:string; meta_description?:string; comfort_note?:string; made_in?:'Vietnam'|'Indonesia'|'China'; addons?:ProductAddon[]; id: number; slug?:string; name: string; thumbnail_image: string; main_price: string; stroked_price: string; has_discount: boolean; rating: number; photos?: {path:string}[]; link?: string; description?: string; rating_count?: number; brand?: {name:string;logo:string}; choice_options?:{name:string;title:string;options:string[]}[];current_stock?:number;unit?:string;calculable_price?:number;variants?:Variant[];min_qty?:number;stock_visibility_state?:'quantity'|'text'|'hide';video_link?:string;video_provider?:string;reviews?:ProductReview[];related?:Product[];top_selling?:Product[];contact?:{phone:string;facebook:string;instagram:string}; };
export type Variant={variant:string;stock:number;price:number;price_string:string;regular_price?:number};
export type Category = {id:number; name:string; icon:string};
export type Deal = {id:number; slug?:string; title:string; banner:string; date:number};
export type Collection<T> = {data:T[]; meta?: {current_page:number;last_page:number;total:number};search?:{original:string;matched_query:string;corrected:boolean;suggestions:string[]}};
export async function api<T>(path: string, fresh = false): Promise<T> {
  const response = await fetch(`${origin}/api/v2/${path}`, {
    ...(fresh ? {cache: 'no-store' as const} : {next: {revalidate: 60}}),
    signal: AbortSignal.timeout(12000), headers: {Accept:'application/json'}
  });
  if (!response.ok) throw new Error(`Catalog unavailable (${response.status})`);
  return response.json() as Promise<T>;
}
export function image(path:string) { return mediaUrl(path, browserOrigin); }
export type HomepageSection={key:string;title:string;position:number;auto_slide:boolean;seconds:number;href:string;items:Product[]};
export async function homepageSections():Promise<HomepageSection[]>{
 const response=await fetch(`${origin}/api/storefront/v3/home-sections`,{cache:'no-store',signal:AbortSignal.timeout(15000),headers:{Accept:'application/json'}});
 if(!response.ok)throw new Error(`Homepage sections unavailable (${response.status})`);
 return (await response.json()).data;
}
export async function products(params: URLSearchParams) {
 const response=await fetch(`${origin}/api/storefront/v3/products?${params}`,{cache:'no-store',signal:AbortSignal.timeout(15000),headers:{Accept:'application/json'}});
 if(!response.ok)throw new Error(`Search unavailable (${response.status})`);
 return response.json() as Promise<Collection<Product>>;
}
export const product=cache(async(key:number|string)=>{
 const response=await fetch(`${origin}/api/storefront/v3/product/${encodeURIComponent(String(key))}`,{cache:'no-store',headers:{Accept:'application/json'},signal:AbortSignal.timeout(15000)});
 if(response.status===404)return null;
 if(!response.ok)throw new Error(`Product unavailable (${response.status})`);
 const result=await response.json() as Collection<Product>;return result.data[0]||null;
});

export type HomepageBanner={id:string;image:string;mobile_image:string|null;href:string;alt:string;new_tab:boolean};
export async function homepageBanners():Promise<{slides:HomepageBanner[];auto_slide:boolean;seconds:number}>{
 const response=await fetch(`${origin}/api/storefront/v3/home-banners`,{cache:'no-store',signal:AbortSignal.timeout(12000),headers:{Accept:'application/json'}});
 if(!response.ok)throw new Error(`Homepage banners unavailable (${response.status})`);return (await response.json()).data;
}

export type SizeGuideData={instructions_title?:string;video_title?:string;image_caption?:string;steps?:string[];fit_tips?:string[];extra_info?:string;title:string;intro:string;image_url:string|null;youtube_id:string|null;measurements:Record<string,number>;reference:boolean};
export async function sizeGuide():Promise<SizeGuideData>{const response=await fetch(`${origin}/api/storefront/v3/size-guide`,{cache:'no-store',headers:{Accept:'application/json'},signal:AbortSignal.timeout(12000)});if(!response.ok)throw new Error('Size guide unavailable');return (await response.json()).data;}

export async function homepageProductImages(){const r=await fetch(`${origin}/api/storefront/v3/home-product-images`,{cache:'no-store',signal:AbortSignal.timeout(12000)});if(!r.ok)throw new Error('Product image showcase unavailable');return (await r.json()).data as import('@/components/HourlyProductShowcase').ProductImageBatch;}
