import {experience} from '@/lib/experience';
import InternationalShop from '@/components/international/Shop';
import Link from 'next/link';
import {api,products,Collection,Category} from '@/lib/catalog';
import {positivePage} from '@/lib/urls.mjs';
import {ProductGrid} from '@/components/Catalog';
import {pageSeo} from '@/lib/seo';
export async function generateMetadata({searchParams}:{searchParams:Promise<Record<string,string|string[]|undefined>>}){const p=await searchParams;const page=positivePage(p.page);const filtered=!!(p.q||p.category||p.sort);return pageSeo(page>1?'Shoes & Sneakers — Page '+page:'Shop Shoes & Sneakers in Bangladesh','Browse available sneakers, current prices and sizes at Footstep Online Bangladesh.',page>1?'/shop?page='+page:'/shop',!filtered);}
export default async function Shop({searchParams}:{searchParams:Promise<Record<string,string|string[]|undefined>>}) {
 const raw=await searchParams; if((await experience()).international)return <InternationalShop params={raw}/>; const get=(k:string)=>typeof raw[k]==='string'?raw[k] as string:'';
 const q=get('q').slice(0,100),category=/^\d+$/.test(get('category'))?get('category'):'';
 const allowed=['relevance','new_arrival','popularity','price_low_to_high','price_high_to_low','top_rated'];
 const sort=allowed.includes(get('sort'))?get('sort'):(q?'relevance':'new_arrival'); const page=positivePage(get('page'));
 const params=new URLSearchParams({name:q,sort_key:sort,page:String(page)}); if(category)params.set('categories',category);
 const [result,categories]=await Promise.all([products(params),api<Collection<Category>>('categories')]);
 function pageLink(n:number){const p=new URLSearchParams({q,category,sort,page:String(n)});return `/shop?${p}`;}
 function categoryLink(id:string){return `/shop?${new URLSearchParams({q,category:id,sort})}`;}
 return <div className="container section"><div className="section-heading"><div><h1>{q?`Search result for “${q}”`:'All Products'}</h1><p>{result.meta?.total??result.data.length} products</p></div></div><div className="next-original-shop"><aside className="next-shop-sidebar"><h3>Categories</h3><Link href={categoryLink('')}>All Categories</Link>{categories.data.map(c=><Link key={c.id} href={categoryLink(String(c.id))}>{c.name}</Link>)}</aside><div className="next-shop-main">
 <form className="filters" action="/shop"><input name="q" defaultValue={q} placeholder="Product, brand বা model" aria-label="Product name" maxLength={100}/><select name="category" defaultValue={category} aria-label="Category"><option value="">All categories</option>{categories.data.map(c=><option key={c.id} value={c.id}>{c.name}</option>)}</select><select name="sort" defaultValue={sort} aria-label="Sort"><option value="relevance">Best match</option><option value="new_arrival">Newest first</option><option value="popularity">Best selling</option><option value="price_low_to_high">Price: low to high</option><option value="price_high_to_low">Price: high to low</option><option value="top_rated">Top rated</option></select><button className="button">Search</button></form>
 {result.search?.corrected&&<div className="search-match-note" role="status">“{q}” এর কাছাকাছি <strong>“{result.search.matched_query}”</strong> দিয়ে পাওয়া product দেখানো হচ্ছে।</div>}
 {result.data.length?<ProductGrid items={result.data}/>:<div className="search-empty-card"><i className="las la-search" aria-hidden="true"/><h2>কোনো product পাওয়া যায়নি</h2><p>ছোট নাম, brand বা model দিয়ে আবার খুঁজুন। যেমন Nike, Hoka বা Invincible।</p>{result.search?.suggestions?.map(word=><Link key={word} href={`/shop?${new URLSearchParams({q:word,category,sort})}`}>“{word}” দিয়ে খুঁজুন →</Link>)}{category&&<Link href={`/shop?${new URLSearchParams({q,sort})}`}>Category filter সরান →</Link>}<Link href="/shop">সব product দেখুন →</Link></div>}
 {!!result.data.length&&<nav className="pagination" aria-label="Pagination">{page>1&&<Link href={pageLink(page-1)}>← Previous</Link>}<span>Page {page} of {result.meta?.last_page||1}</span>{page<(result.meta?.last_page||1)&&<Link href={pageLink(page+1)}>Next →</Link>}</nav>}</div></div></div>;
}
