import {notFound,redirect} from 'next/navigation';
import {dealHref} from '@/lib/design';
export default async function DealPage({params}:{params:Promise<{id:string}>}){const {id}=await params;if(!/^\d+$/.test(id))notFound();redirect(dealHref(Number(id)));}
