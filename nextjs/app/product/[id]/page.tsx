import {productSeo,productSchema,breadcrumbs,jsonLd,productPath} from '@/lib/seo';
import {notFound} from 'next/navigation';
import Link from 'next/link';
import {product,image,browserOrigin} from '@/lib/catalog';
import {safeDescription} from '@/lib/description.mjs';
import {productGallery} from '@/lib/product-media.mjs';
import {productDiscount} from '@/lib/product-discount.mjs';
import Gallery from '@/components/Gallery';
import ProductPurchase from '@/components/ProductPurchase';
import ProductTabs from '@/components/ProductTabs';
import {ProductCard} from '@/components/Catalog';
import OriginalCarousel from '@/components/OriginalCarousel';
import {design,productHref} from '@/lib/design';
export async function generateMetadata({params}:{params:Promise<{id:string}>}){const p=await product((await params).id);if(!p)notFound();return productSeo(p);}
export default async function ProductPage({params,searchParams}:{params:Promise<{id:string}>;searchParams:Promise<{size?:string}>}) {
 const p=await product((await params).id);if(!p)notFound();
 const initialSize=(await searchParams).size||'';
 const photos=productGallery(p.thumbnail_image,p.photos||[],browserOrigin);
 const href=p.slug?`/product/${encodeURIComponent(p.slug)}`:productHref(p.id);
 const reviews=p.reviews||[],rating=reviews.length?reviews.reduce((n,r)=>n+r.rating,0)/reviews.length:0;
 return <div className="container section product-page"><script type="application/ld+json" dangerouslySetInnerHTML={{__html:jsonLd(productSchema(p))}}/><script type="application/ld+json" dangerouslySetInnerHTML={{__html:jsonLd(breadcrumbs([{name:'Home',path:'/'},{name:'Products',path:'/shop'},{name:p.name,path:productPath(p)}]))}}/><div className="breadcrumbs"><Link href="/">Home</Link> / <Link href="/shop">Products</Link> / {p.brand?.name||'Footstep'}</div>
 <section className="product-detail"><Gallery key={p.id} images={photos} title={p.name} discount={productDiscount(p.variants||[],p.min_qty||1)}/><div className="product-info"><h1>{p.name}</h1><div className="rating rating-sm" aria-label={`${rating.toFixed(1)} out of 5`}>{[1,2,3,4,5].map(n=><i key={n} className={`las la-star ${n<=Math.round(rating)?'active':''}`}/>)} <span className="opacity-50 fs-12">({reviews.length} reviews)</span></div>
 <div className="product-seller-origin d-flex align-items-center flex-wrap border-top border-bottom mt-3 py-3"><div><small className="opacity-50">Sold by:</small><br/>Inhouse product</div>{p.brand?.logo&&<img className="ml-4" src={image(p.brand.logo)} alt={p.brand.name} style={{width:80,height:40,objectFit:'contain'}}/>}<span className="product-origin">Made in <strong>{p.made_in||'Vietnam'}</strong></span></div>
 <ProductPurchase initialVariant={initialSize} id={p.id} name={p.name} url={`${browserOrigin}${href}`} image={image(p.thumbnail_image)} oldPrice={p.stroked_price} mainPrice={p.main_price} variants={p.variants||[]} minQty={p.min_qty||1} comfortNote={p.comfort_note} addons={p.addons||[]} stockVisibility={p.stock_visibility_state||'hide'} phone={(p.contact?.phone||design.contactPhone).replace(/[^0-9]/g,'')} facebook={p.contact?.facebook||design.facebook}/>
 </div></section>
 <div className="product-lower">
 <ProductTabs id={p.id} description={safeDescription(p.description,browserOrigin)} video={p.video_link} reviews={reviews}/></div>
 {!!p.related?.length&&<section className="description related-products"><h2>Related products</h2><OriginalCarousel autoSlide>{p.related.map(item=><ProductCard key={item.id} product={item}/>)}</OriginalCarousel></section>}
 </div>;
}
