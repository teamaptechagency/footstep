import Link from 'next/link';
import {pageSeo} from '@/lib/seo';
import {api,Collection,Category,image} from '@/lib/catalog';
export const metadata=pageSeo('Shoe Categories in Bangladesh','Browse running shoes, sneakers, casual shoes and more categories at Footstep Online.','/categories');
export default async function Categories(){const categories=await api<Collection<Category>>('categories');return <div className="container section"><h1 className="h4 mb-4">All Categories</h1><div className="next-category-grid">{categories.data.map(c=><Link key={c.id} href={`/shop?category=${c.id}`}><img src={image(c.icon)} alt=""/><strong>{c.name}</strong></Link>)}</div></div>;}
