import {NextRequest} from 'next/server';
import {origin,browserOrigin} from '@/lib/catalog';
import {allowedCommercePath,replaceLaravelOrigin} from '@/lib/checkout-proxy.mjs';
export const dynamic='force-dynamic';
async function proxy(request:NextRequest,{params}:{params:Promise<{path:string[]}>}){
 const path=(await params).path.join('/');
 if(!allowedCommercePath(path,request.method))return new Response('Not found',{status:404});
 if(path==='order-confirmed'&&request.method==='GET')return new Response(null,{status:307,headers:{Location:'/order-confirmed','Cache-Control':'private, no-store'}});
 if(request.nextUrl.search.length>2048)return new Response('Request too large',{status:414});
 const publicOrigin=process.env.STOREFRONT_ORIGIN||`${request.nextUrl.protocol}//${request.headers.get('host')}`;
 if(request.method==='POST'&&request.headers.get('origin')!==publicOrigin)return new Response('Invalid request origin',{status:403});
 if(Number(request.headers.get('content-length')||0)>2*1024*1024)return new Response('Request too large',{status:413});
 const headers=new Headers({Accept:request.headers.get('accept')||'*/*'});
 for(const key of ['cookie','content-type','x-requested-with','x-csrf-token','x-xsrf-token','dnt','sec-gpc']){const value=request.headers.get(key);if(value)headers.set(key,value);}
 let body:ArrayBuffer|undefined;
 if(request.method==='POST'){body=await request.arrayBuffer();if(body.byteLength>2*1024*1024)return new Response('Request too large',{status:413});}
 try{
 const upstream=await fetch(`${origin}/${path}${request.nextUrl.search}`,{method:request.method,headers,body,cache:'no-store',redirect:'manual',signal:AbortSignal.timeout(30000)});
 const outputHeaders=new Headers({'Cache-Control':'private, no-store','X-Frame-Options':'SAMEORIGIN','Content-Type':upstream.headers.get('content-type')||'text/plain; charset=utf-8'});
 for(const cookie of upstream.headers.getSetCookie()){outputHeaders.append('Set-Cookie',cookie.replace(/;\s*domain=[^;]+/ig,''));}
 const location=upstream.headers.get('location');if(location){if([origin,browserOrigin].some(value=>location.startsWith(value+'/')))outputHeaders.set('Location',replaceLaravelOrigin(location,[origin,browserOrigin]));else return new Response('Session redirect: reopen checkout',{status:409});}
 const contentType=upstream.headers.get('content-type')||'';
 if(contentType.includes('text/html')||contentType.includes('application/json')){
  let text=replaceLaravelOrigin(await upstream.text(),[origin,browserOrigin]);
  return new Response(text,{status:upstream.status,headers:outputHeaders});
 }
 return new Response(upstream.body,{status:upstream.status,headers:outputHeaders});
 }catch{return new Response('Checkout server unavailable. Please try again.',{status:502,headers:{'Cache-Control':'no-store'}});}
}
export {proxy as GET,proxy as POST};
