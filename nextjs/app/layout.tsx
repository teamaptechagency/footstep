import {experience} from '@/lib/experience';
import {InternationalHeader,InternationalFooter,DomesticLocation} from '@/components/international/Chrome';
import ProductRoutePrefetch from '@/components/ProductRoutePrefetch';
import StorefrontGoogleAnalytics from '@/components/StorefrontGoogleAnalytics';
import {seoConfig} from '@/lib/seo-config';
import StorefrontMeta from '@/components/StorefrontMeta';
import StorefrontAnalytics from '@/components/StorefrontAnalytics';
import type { Metadata } from 'next';
import {pageSeo,siteUrl,indexable,jsonLd} from '@/lib/seo';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import MobileNav from '@/components/MobileNav';
import {browserOrigin,image} from '@/lib/catalog';
import {design,legacyHref} from '@/lib/design';
import './original-adapter.css';
import './hourly-product-showcase.css';
import './product-parity.css';
import './not-found.css';
const baseMetadata:Metadata = {icons:{icon:[{url:'/footstep-icon-32.png?v=20261110',type:'image/png',sizes:'32x32'},{url:'/footstep-icon-16.png?v=20261110',type:'image/png',sizes:'16x16'}],shortcut:'/favicon.ico?v=20261110',apple:[{url:'/footstep-icon-180.png?v=20261110',sizes:'180x180'}]},metadataBase:new URL(siteUrl),title:{default:'Sneakers & Shoes in Bangladesh | Footstep Online',template:'%s | Footstep Online'},description:'Browse sneakers and shoes at Footstep Online Bangladesh. Compare current prices, available sizes and product photos, with cash on delivery.',robots:{index:indexable,follow:true},verification:{google:process.env.GOOGLE_SITE_VERIFICATION||undefined}};
export async function generateMetadata():Promise<Metadata>{const config=await seoConfig();return {...baseMetadata,verification:{google:config.google_verification||process.env.GOOGLE_SITE_VERIFICATION||undefined}};}
export default async function RootLayout({children}:Readonly<{children:React.ReactNode}>) {
 const google=await seoConfig();const region=await experience();
 return <html lang={region.international?"en":"bn"} data-experience={region.international?"international":"bangladesh"}><head>
 {['vendors','aiz-core','custom-style','mobile-navigation','home-refresh','product-square','product-thumbnail-zoom','product-order-tools','flash-deal-groups','flash-collection'].map(name=><link key={name} rel="stylesheet" href={`/legacy/assets/css/${name}.css`}/>)}
 <link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600,700&display=swap" rel="stylesheet"/>
 {region.international&&<><link rel="stylesheet" href="/international/styles.css"/><link rel="stylesheet" href="/international/pages.css"/><link rel="stylesheet" href="/international/integration.css"/></>}<link rel="stylesheet" href="/international/region.css"/></head><body data-layout-theme="classic" data-color-pack="emerald" style={{'--primary':design.palette.primary,'--hov-primary':design.palette.hover,'--soft-primary':'rgba(0,138,69,.15)'} as React.CSSProperties}><div className="aiz-main-wrapper d-flex flex-column">
 {region.source==='preview'&&<div className="fs-preview" data-fs-preview>International design preview <a href="/international-preview?exit=1">Use automatic location</a></div>}{region.international?<InternationalHeader/>:<Header logo={image(design.logo||'')} menu={design.menu.map(item=>({...item,href:legacyHref(item.href)}))} backend={browserOrigin}/>}<main id="main">{children}</main><ProductRoutePrefetch/><StorefrontAnalytics/><StorefrontGoogleAnalytics config={google.ga4}/><StorefrontMeta/>{region.international?<InternationalFooter/>:<><Footer/><MobileNav backend={browserOrigin}/>{region.source!=='disabled'&&<DomesticLocation/>}</>}
 </div></body></html>;
}
