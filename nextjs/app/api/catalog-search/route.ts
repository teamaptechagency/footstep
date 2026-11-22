import {NextRequest} from 'next/server';
import {origin} from '@/lib/catalog';
export const dynamic='force-dynamic';
export async function GET(request:NextRequest){
 const q=(request.nextUrl.searchParams.get('q')||'').trim().slice(0,100);
 if(q.length<2)return Response.json({data:[],meta:{total:0}},{headers:{'Cache-Control':'no-store'}});
 try{
  const params=new URLSearchParams({name:q,sort_key:'relevance',per_page:'6'});
  const response=await fetch(`${origin}/api/storefront/v3/products?${params}`,{cache:'no-store',signal:AbortSignal.timeout(10000),headers:{Accept:'application/json'}});
  if(!response.ok)return Response.json({error:'Search unavailable'},{status:503});
  return Response.json(await response.json(),{headers:{'Cache-Control':'private, no-store'}});
 }catch{return Response.json({error:'Search unavailable'},{status:503});}
}
