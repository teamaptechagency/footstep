import Script from 'next/script';
import {internationalHeader,internationalFooter,internationalDialogs,regionDialog} from '@/lib/international-chrome';
export function InternationalHeader(){return <div dangerouslySetInnerHTML={{__html:internationalHeader}}/>;}
export function InternationalFooter(){return <><div dangerouslySetInnerHTML={{__html:internationalFooter+internationalDialogs}}/><Script src="/international/shell.js" type="module" strategy="afterInteractive"/></>;}
export function DomesticLocation(){return <><div className="fs-domestic-location"><button type="button" data-fs-open="fs-region">Location settings</button><a href="https://db-ip.com" target="_blank" rel="noopener noreferrer">IP Geolocation by DB-IP</a></div><div dangerouslySetInnerHTML={{__html:regionDialog}}/><Script src="/international/shell.js" type="module" strategy="afterInteractive"/></>;}
