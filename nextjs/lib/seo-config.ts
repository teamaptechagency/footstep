import 'server-only';
import {cache} from 'react';
import {origin} from './catalog';
type Config={ga4:{id:string|null;require_consent:boolean};google_verification:string|null};
export const seoConfig=cache(async():Promise<Config>=>{try{const r=await fetch(origin+'/api/storefront/v3/seo-config',{next:{revalidate:60},signal:AbortSignal.timeout(5000)});if(r.ok)return r.json();}catch{}return {ga4:{id:null,require_consent:true},google_verification:null};});
