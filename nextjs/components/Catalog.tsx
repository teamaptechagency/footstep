import Link from 'next/link';
import {Product,image,browserOrigin} from '@/lib/catalog';
import Media from './Media';
import {productHref} from '@/lib/design';
import OrderButton from './OrderButton';
export function ProductCard({product:p}: {product:Product}) {
  const amount=(s:string)=>Number(String(s).replace(/[^0-9.]/g,''));
  const old=amount(p.stroked_price),now=amount(p.main_price),discount=old>now?Math.round((old-now)*100/old):0;
  return <article className="aiz-card-box border border-light rounded hov-shadow-md mt-1 mb-2 has-transition bg-white next-product-card">
    <Link href={productHref(p.id,p.slug||p.link)} className="product-photo d-block position-relative" prefetch={false}>
      <Media src={image(p.thumbnail_image)} alt={p.name}/>
      {discount>0&&<span className="badge-custom">OFF<span className="box ml-1 mr-0">{discount}%</span></span>}
    </Link>
    <div className="p-md-3 p-2 text-left">
      <div className="fs-15">{p.has_discount&&<del className="fw-600 opacity-50 mr-1">{p.stroked_price}</del>}<span className="fw-700 text-primary">{p.main_price}</span></div>
      <div className="rating rating-sm mt-1" aria-label={`${p.rating||0} out of 5`}>{[1,2,3,4,5].map(i=><i key={i} className={`las la-star ${i<=(p.rating||0)?'active':''}`}/>)}</div>
      <h3 className="fw-600 fs-13 text-truncate-2 lh-1-4 mb-0 h-35px"><Link href={productHref(p.id,p.slug||p.link)} prefetch={false} className="d-block text-reset">{p.name}</Link></h3>
      <OrderButton className="btn btn-primary btn-block mt-2 next-order" id={p.id}><i className="las la-shopping-bag"/> Order Now</OrderButton>
    </div>
  </article>;
}
export function ProductGrid({items}: {items:Product[]}) {
  return items.length?<div className="product-grid">{items.map(p=><ProductCard key={p.id} product={p}/>)}</div>:<div className="empty">কোনো প্রোডাক্ট পাওয়া যায়নি। অন্য শব্দ বা category দিয়ে খুঁজুন।</div>;
}
