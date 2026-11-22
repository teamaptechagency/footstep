import {pageSeo} from '@/lib/seo';
import './brand-header.css';
import {notFound} from 'next/navigation';
import Link from 'next/link';
import {api,Collection,Product,Deal,image} from '@/lib/catalog';
import {design,productHref} from '@/lib/design';
import Media from '@/components/Media';
import OrderButton from '@/components/OrderButton';
export async function generateMetadata({params}:{params:Promise<{slug:string}>}){const {slug}=await params;const groups=await api<Collection<Deal>>('flash-deals',true);const group=groups.data.find(g=>g.slug===slug||String(g.id)===slug);if(!group)notFound();return pageSeo(group.title+' — Shoe Collection','Browse '+group.title+' products, current prices and available sizes at Footstep Online.','/flash-deal/'+encodeURIComponent(group.slug||String(group.id)));}
export default async function CollectionPage({params}:{params:Promise<{slug:string}>}){
 const {slug}=await params;
 const groups=await api<Collection<Deal>>('flash-deals',true);const group=groups.data.find(g=>g.slug===slug||String(g.id)===slug);if(!group)notFound();const id=group.id;
 const products=await api<Collection<Product>>(`flash-deal-products/${id}`,true);
 return <><header className="flash-brand-header"><div><Link href="/" aria-label="Footstep home"><img src={image(design.logo||'')} alt="Footstep online"/></Link><span>{group.title}</span></div></header><div className="fsale flash-brand-collection"><div className="container"><h1 className="sr-only">{group.title}</h1>
 <section id="sale-products" className="fsale-products"><div className="fsale-grid">{products.data.map(p=><article key={p.id} className="fsale-card aiz-card-box"><Link className="fsale-card-image" href={`${productHref(p.id)}?flash_deal=${id}`}><Media src={image(p.thumbnail_image)} alt={p.name}/><span className="fsale-view">View details ↗</span></Link><div className="fsale-card-body"><span className="fsale-card-eyebrow">THE FLASH COLLECTION</span><h3><Link href={`${productHref(p.id)}?flash_deal=${id}`}>{p.name}</Link></h3><div className="fsale-price"><strong>{p.main_price}</strong>{p.has_discount&&<del>{p.stroked_price}</del>}</div><div className="fsale-card-footer"><span>Select your size at checkout</span><OrderButton id={p.id} className="fsale-order"><i className="las la-shopping-bag"/> Order Now</OrderButton></div></div></article>)}</div></section>
 <div className="fsale-bottom"><span>GOOD PAIRS. BETTER PRICES.</span><Link href="/shop">Keep exploring →</Link></div></div></div></>;
}
