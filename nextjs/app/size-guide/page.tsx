import {pageSeo} from '@/lib/seo';
import Link from 'next/link';
import {image,sizeGuide,product} from '@/lib/catalog';
import SizeGuideProduct from '@/components/SizeGuideProduct';
import SizeGuideContent from '@/components/SizeGuideContent';
export const metadata=pageSeo('Shoe Size Guide','Learn how to measure your feet and compare shoe sizes before ordering from Footstep Online Bangladesh.','/size-guide');
export default async function SizeGuidePage({searchParams}:{searchParams:Promise<{product?:string;size?:string;quantity?:string}>}){
 const query=await searchParams;
 const [guide,p]=await Promise.all([sizeGuide(),/^\d{1,10}$/.test(query.product||'')?product(query.product!):Promise.resolve(null)]);
 const min=p?.min_qty||1;
 const variant=p?.variants?.find(v=>v.variant===query.size&&v.stock>=min)||p?.variants?.find(v=>v.stock>=min);
 const quantity=Math.min(Math.max(min,Math.floor(Number(query.quantity)||min)),99,variant?.stock||min);
 const href=p?`/product/${encodeURIComponent(p.slug||String(p.id))}${variant?`?size=${encodeURIComponent(variant.variant)}`:''}`:'/shop';
 return <section className="size-guide-page container" style={p?{paddingBottom:180}:undefined}>{p&&<SizeGuideProduct id={p.id} name={p.name} image={image(p.thumbnail_image)} href={href} price={variant?.price_string||p.main_price} variant={variant?.variant} quantity={quantity} available={!!variant&&variant.stock>=min}/>}<SizeGuideContent guide={guide} imageUrl={image(guide.image_url||'')}/></section>;
}
