import {mediaUrl} from './urls.mjs';

export function productGallery(thumbnail, photos = [], origin) {
 const sources = photos.map(photo => photo?.path);
 const urls = sources.filter(value => typeof value === 'string' && value.trim())
  .map(value => mediaUrl(value, origin)).filter(value => value !== '/placeholder.svg');
 // Product detail uses the saved gallery order; thumbnail is only a fallback.
 return urls.length ? [...new Set(urls)] : [mediaUrl(thumbnail, origin)];
}

export function videoEmbed(raw){
 try{
  const u=new URL(String(raw||''));if(!['https:','http:'].includes(u.protocol))return null;
  const host=u.hostname.toLowerCase().replace(/^www\./,'');let id;
  if(host==='youtu.be')id=u.pathname.split('/')[1];
  if(['youtube.com','m.youtube.com','youtube-nocookie.com'].includes(host))id=u.searchParams.get('v')||u.pathname.match(/^\/(?:embed|shorts)\/([^/]+)/)?.[1];
  if(id&&/^[a-zA-Z0-9_-]{11}$/.test(id))return 'https://www.youtube-nocookie.com/embed/'+id;
  if(['vimeo.com','player.vimeo.com'].includes(host)){id=u.pathname.match(/(?:\/video)?\/(\d+)\/?$/)?.[1];if(id)return 'https://player.vimeo.com/video/'+id;}
  if(host==='dailymotion.com'){id=u.pathname.match(/^\/video\/([a-zA-Z0-9]+)/)?.[1];if(id)return 'https://www.dailymotion.com/embed/video/'+id;}
 }catch{}return null;
}
