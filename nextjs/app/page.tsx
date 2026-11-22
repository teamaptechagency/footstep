import {experience} from '@/lib/experience';
import InternationalHome from '@/components/international/Home';
import Link from 'next/link';
import {pageSeo,jsonLd,siteUrl} from '@/lib/seo';
export const metadata=pageSeo('Sneakers & Shoes in Bangladesh','Shop sneakers and shoes at Footstep Online Bangladesh. Browse Nike, Adidas and other collections, compare available sizes and order with cash on delivery.','/');
import {api,Collection,Product,Category,image,homepageSections,homepageBanners,homepageProductImages} from '@/lib/catalog';
import OriginalSection from '@/components/OriginalSection';
import OriginalHero from '@/components/OriginalHero';
import {design,legacyHref,productHref} from '@/lib/design';
import Media from '@/components/Media';
export default async function Home() {
 if((await experience()).international)return <InternationalHome/>;
 const [categories,sliders,today,all,sections,showcase]=await Promise.all([
  api<Collection<Category>>('categories'),
  homepageBanners(),
  api<Collection<Product>>('products/todays-deal'),
  api<Collection<Product>>('products/search?sort_key=new_arrival'),
  homepageSections(),
  homepageProductImages().catch(()=>undefined)
 ]);
 const promo=(n:'1'|'2'|'3')=>design.banners[n].length>0&&<div className="container mb-4"><div className="row gutters-10">{design.banners[n].map(b=><div key={b.image} className="col"><Link href={legacyHref(b.href)}><img className="img-fluid w-100" src={image(b.image)} alt="Footstep promo" loading="lazy"/></Link></div>)}</div></div>;
 return <><h1 className="sr-only">Sneakers &amp; Shoes in Bangladesh — Footstep Online</h1><script type="application/ld+json" dangerouslySetInnerHTML={{__html:jsonLd({"@context":"https://schema.org","@type":"Organization",name:"Footstep Online",url:siteUrl,logo:image(design.logo||'')})}}/>
 <section className="home-banner-area home-refresh"><div className="container"><div className={`home-refresh-grid ${today.data.length?'':'without-today'}`}>
 <aside className="home-refresh-categories d-none d-lg-block"><div className="aiz-category-menu bg-white"><div className="all-category d-flex justify-content-between align-items-center"><span className="fw-600">Categories</span><Link href="/categories"><span>All categories &gt;</span></Link></div><ul className="list-unstyled categories mb-0">{categories.data.map(c=><li className="category-nav-element" key={c.id}><Link href={`/shop?category=${c.id}`} className="d-flex align-items-center text-reset"><img src={image(c.icon)} className="cat-image mr-2" alt="" width="16" height="16"/><span>{c.name}</span></Link></li>)}</ul></div></aside>
 <OriginalHero showcase={showcase} slides={sliders.slides.map(s=>({...s,image:image(s.image),mobile_image:s.mobile_image?image(s.mobile_image):null,href:legacyHref(s.href)}))} autoSlide={sliders.auto_slide} intervalSeconds={sliders.seconds} today={today.data.map(p=>({id:p.id,href:productHref(p.id),name:p.name,image:image(p.thumbnail_image),price:p.main_price,oldPrice:p.has_discount?p.stroked_price:''}))}/>
 </div></div></section>
 {promo('1')}{sections.map((section,index)=><div key={section.key} data-home-section={section.key}>{index===1&&promo('2')}<OriginalSection title={section.title} items={section.items} carousel autoSlide={section.auto_slide} intervalSeconds={section.seconds} href={section.href}/></div>)}
 <OriginalSection title="Products" items={all.data} href="/shop"/>{promo('3')}
 <section className="mb-4"><div className="container"><div className="row gutters-10">{[{title:'Top 10 Categories',href:'/categories',label:'View All Categories',items:design.topCategories.map(c=>({...c,href:`/shop?category=${c.id}`}))},{title:'Top 10 Brands',href:legacyHref('/brands'),label:'View All Brands',items:design.topBrands.map(b=>({...b,href:legacyHref(`/brand/${b.slug}`)}))}].map(section=><div className="col-lg-6" key={section.title}><div className="d-flex mb-3 align-items-baseline border-bottom"><h3 className="h5 fw-700 mb-0"><span className="border-bottom border-primary border-width-2 pb-3 d-inline-block">{section.title}</span></h3><Link href={section.href} className="ml-auto mr-0 btn btn-primary btn-sm shadow-md">{section.label}</Link></div><div className="row gutters-5">{section.items.map(item=><div className="col-sm-6" key={item.id}><Link href={item.href} className="bg-white border d-block text-reset rounded p-2 hov-shadow-md mb-2"><div className="row align-items-center no-gutters"><div className="col-3 text-center"><img className="img-fluid h-60px" src={image(item.image||'')} alt={item.name} loading="lazy"/></div><div className="col-7 pl-3 fs-14 fw-600">{item.name}</div><div className="col-2 text-center"><i className="la la-angle-right text-primary"/></div></div></Link></div>)}</div></div>)}</div></div></section>
 <section className="container mb-4"><div className="bg-white border rounded p-4"><h2 className="h5 fw-700">Find sneakers and shoes in Bangladesh</h2><p>Explore current prices, available sizes and product photos before choosing your next pair. Check each product’s grade and description, and use our size guide before ordering.</p><div className="d-flex flex-wrap" style={{gap:16}}><Link href="/brand/Nike-TCZrK">Nike collection →</Link><Link href="/brand/Adidas-lMuUj">Adidas collection →</Link><Link href="/size-guide">Size Guide →</Link><Link href="/buying-guide">Buying Guide →</Link></div></div></section> </>;
}
