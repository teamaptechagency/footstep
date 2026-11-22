import {NextResponse,type NextRequest} from 'next/server';
export function proxy(_request:NextRequest){const response=NextResponse.next();response.headers.set('Cache-Control','private, no-store');response.headers.set('Vary','Cookie, X-Footstep-Client-IP');return response;}
export const config={matcher:'/((?!_next|api/|commerce/|regional-commerce/|international/|legacy/|favicon|robots|sitemap|footstep-icon).*)'};
