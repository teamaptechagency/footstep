import Link from 'next/link';

export default function NotFoundState({product=false}:{product?:boolean}) {
 return <section className="footstep-not-found" aria-labelledby="not-found-title">
  <div className="not-found-card">
   <div className="not-found-art" aria-hidden="true">
    <span className="not-found-number">{product?'Oops!':'404'}</span>
    <svg viewBox="0 0 96 96" fill="none"><path d="M22 32h52v44H22zM32 32v-8a16 16 0 0 1 32 0v8" stroke="currentColor" strokeWidth="3.5" strokeLinejoin="round"/><path d="M37 49h1m20 0h1M38 63c6-4 14-4 20 0" stroke="currentColor" strokeWidth="4" strokeLinecap="round"/><path d="m14 18-5-5m73 5 5-5M8 48H3m90 0h-5" stroke="currentColor" strokeWidth="3" strokeLinecap="round"/></svg>
   </div>
   <p className="not-found-eyebrow">{product?'PRODUCT UNAVAILABLE':'A LITTLE OFF TRACK'}</p>
   <h1 id="not-found-title">{product?'This product is not available for now.':'Page not found.'}</h1>
   <p className="not-found-message">{product?'Please check others.':'The page you’re looking for may have moved or no longer exists.'}</p>
   <div className="not-found-actions"><Link href="/" className="not-found-home"><span aria-hidden="true">←</span> Go Home</Link><Link href="/shop" className="not-found-browse">Browse products <span aria-hidden="true">↗</span></Link></div>
  </div>
 </section>;
}
