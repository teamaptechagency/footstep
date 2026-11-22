export function mediaUrl(value, origin) {
  if (typeof value !== 'string' || !value.trim()) return '/placeholder.svg';
  try {
    const url = new URL(value, `${origin}/public/`);
    if (!['http:', 'https:'].includes(url.protocol)) return '/placeholder.svg';
    return url.href;
  } catch { return '/placeholder.svg'; }
}
export function positivePage(value) {
  const n = Number(value);
  return Number.isSafeInteger(n) && n > 0 && n <= 10000 ? n : 1;
}
export function plainText(html) {
  return String(html || '').replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, '').replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, '').replace(/<[^>]*>/g, ' ').replace(/&nbsp;/g,' ').replace(/&amp;/g,'&').replace(/\s+/g, ' ').trim();
}
