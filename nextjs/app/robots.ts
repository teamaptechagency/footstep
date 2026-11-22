import type {MetadataRoute} from 'next';
import {indexable,siteUrl} from '@/lib/seo';
export default function robots():MetadataRoute.Robots{return {rules:{userAgent:'*',...(indexable?{allow:'/',disallow:['/admin','/api/','/commerce/','/checkout','/cart','/users/','/login','/register','/password/','/shared-invoice/','/order-confirmed','/order-track','/track-your-order','/purchase_history','/wishlists','/compare','/search','/shop?q=']}:{disallow:'/'})},sitemap:siteUrl+'/sitemap.xml',host:siteUrl};}
