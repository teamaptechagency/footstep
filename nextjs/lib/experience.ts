import 'server-only';
import {internationalEnabled} from './interface-settings.mjs';
import fs from 'node:fs';
import path from 'node:path';
import {isIP} from 'node:net';
import {Reader,type CountryResponse} from 'mmdb-lib';
import {headers,cookies} from 'next/headers';
import {cache} from 'react';
import {chooseRegion} from './region-rules.mjs';
type CountryLookup=CountryResponse & {country_code?:string};
let reader:Reader<CountryLookup>|null=null;
function countryForIP(value:string|null){
 const ip=(value||'').replace(/^::ffff:/,'').trim();
 if(!isIP(ip)||/^(127\.|10\.|192\.168\.|169\.254\.|172\.(1[6-9]|2\d|3[01])\.)/.test(ip)||ip==='::1'||/^(fc|fd|fe80):/i.test(ip))return null;
 try{const db=reader??(reader=new Reader<CountryLookup>(fs.readFileSync(path.join(process.cwd(),'geo-data/country.mmdb'))));const value=db.get(ip);return value?.country?.iso_code||value?.country_code||null;}catch{return null;}
}
export const experience=cache(async()=>{
 const h=await headers(),c=await cookies();
 const host=(h.get('host')||'').toLowerCase().split(':')[0];
 if(!['footsteponline.com','footstep.astrallabs.uk','127.0.0.1','localhost'].includes(host))return {international:false,country:null,source:'host'};
 if(!internationalEnabled())return {international:false,country:null,source:'disabled'};
 if(c.get('footstep_ui_preview')?.value==='international')return {international:true,country:null,source:'preview'};
 return chooseRegion(countryForIP(h.get('x-footstep-client-ip')),c.get('footstep_geo_region')?.value);
});
