'use client';
import Link from 'next/link';
import OrderButton from './OrderButton';

type Props={id:number;name:string;image:string;href:string;price:string;variant?:string;quantity:number;available:boolean};
export default function SizeGuideProduct(p:Props){
 return <>
  <nav className="guide-product-return" aria-label="Return to product"><Link href={p.href}>← পণ্যে ফিরে যান</Link><Link href="#guide-product-order">এই পণ্যটি অর্ডার করুন ↓</Link></nav>
  <aside id="guide-product-order" className="guide-product-dock" aria-label="Selected product">
   <Link href={p.href} className="guide-product-summary"><img src={p.image} alt="" width="48" height="60"/><span><strong>{p.name}</strong><small>{p.price}{p.variant?` · Size: ${p.variant}`:''}</small><span className="guide-product-back">← পণ্যটি দেখুন</span></span></Link>
   <OrderButton id={p.id} variant={p.variant} quantity={p.quantity} disabled={!p.available} className="guide-product-order">{p.available?'Order Now':'Unavailable'}</OrderButton>
  </aside>
  <style jsx>{`
   .guide-product-return{display:flex;justify-content:space-between;flex-wrap:wrap;gap:10px;padding:12px 16px;margin-bottom:16px;background:#fff;border:1px solid #dce6e1;border-radius:10px;font-weight:700;color:#087f56}
   .guide-product-space{height:95px}
   .guide-product-dock{position:fixed;z-index:1030;bottom:0;left:0;right:0;display:flex;align-items:center;gap:14px;padding:12px max(16px,calc((100vw - 1140px)/2));background:#fff;border-top:1px solid #d7e4dd;box-shadow:0 -4px 20px #193d2714}
   .guide-product-summary{display:flex;align-items:center;gap:10px;min-width:0;flex:1;color:#183d31}
   .guide-product-summary img{object-fit:cover;border-radius:6px;flex-shrink:0}
   .guide-product-summary strong{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;font-size:13px;line-height:1.4}
   .guide-product-summary small{display:block;color:#087f56;font-weight:700}
   .guide-product-back{font-size:11px;text-decoration:underline}
   .guide-product-dock :global(.guide-product-order){flex-shrink:0;min-height:46px;border:0;border-radius:8px;padding:10px 18px;color:white;background:#087f56;font-weight:750}
   .guide-product-dock :global(.guide-product-order:disabled){opacity:.5}
   @media(max-width:767px){.guide-product-dock{bottom:calc(72px + env(safe-area-inset-bottom));padding:10px 12px;gap:10px}.guide-product-space{height:170px}.guide-product-dock :global(.guide-product-order){padding:10px 12px;font-size:12px}.guide-product-summary img{width:42px;height:54px}}
   @media print{.guide-product-dock,.guide-product-return,.guide-product-space{display:none}}
  `}</style>
 </>;
}
