export function chooseRegion(country, cookie, now=Date.now()) {
 const ipCode=/^[A-Z]{2}$/.test(country||'')&&!['ZZ','XX'].includes(country)?country:null;
 if(ipCode&&ipCode!=='BD')return {international:true,country:ipCode,source:'ip'};
 const match=/^(BD|INTL)\.(\d{13})$/.exec(cookie||'');
 if(match){const age=now-Number(match[2]);if(age>=-120000&&age<21600000)return {international:match[1]==='INTL',country:match[1]==='BD'?'BD':null,source:'location'};}
 const code=/^[A-Z]{2}$/.test(country||'')&&!['ZZ','XX'].includes(country)?country:null;
 return {international:!!code&&code!=='BD',country:code,source:code?'ip':'unknown'};
}
export function inRing(x,y,ring){let inside=false;for(let i=0,j=ring.length-1;i<ring.length;j=i++){const [xi,yi]=ring[i],[xj,yj]=ring[j];if(((yi>y)!==(yj>y))&&(x<(xj-xi)*(y-yi)/(yj-yi)+xi))inside=!inside;}return inside;}
export function locationRegion(lat,lon,accuracy,geometry){
 if(!Number.isFinite(lat)||!Number.isFinite(lon)||lat< -90||lat>90||lon< -180||lon>180||!Number.isFinite(accuracy)||accuracy<0||accuracy>25000)return null;
 const polys=geometry.type==='Polygon'?[geometry.coordinates]:geometry.type==='MultiPolygon'?geometry.coordinates:[];if(!polys.length)return null;
 const inside=polys.some(rings=>inRing(lon,lat,rings[0])&&!rings.slice(1).some(r=>inRing(lon,lat,r)));
 // Preserve the IP decision near borders or when the accuracy circle is uncertain.
 const scale=Math.cos(lat*Math.PI/180);let nearest=Infinity;
 for(const rings of polys)for(const ring of rings)for(let i=1;i<ring.length;i++){const ax=(ring[i-1][0]-lon)*scale,ay=ring[i-1][1]-lat,bx=(ring[i][0]-lon)*scale,by=ring[i][1]-lat,dx=bx-ax,dy=by-ay,t=Math.max(0,Math.min(1,-(ax*dx+ay*dy)/(dx*dx+dy*dy||1)));nearest=Math.min(nearest,Math.hypot(ax+t*dx,ay+t*dy)*111320);}
 if(nearest<Math.max(2000,accuracy))return null;
 return inside?'BD':'INTL';
}
