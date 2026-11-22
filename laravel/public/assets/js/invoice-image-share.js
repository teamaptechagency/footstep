(() => {
  'use strict';
  const dataElement = document.querySelector('#invoice-share-data');
  const shareButton = document.querySelector('#invoice-image-share');
  if (!dataElement || !shareButton) return;
  const data = JSON.parse(dataElement.textContent);
  const status = document.querySelector('#invoice-image-status');
  const list = document.querySelector('#invoice-image-files');
  const message = () => document.querySelector('#invoice-message').value;
  const urls = [];
  let files = [];
  const safeOrder = data.order.replace(/[^a-z0-9_-]/gi, '-').slice(0, 80) || 'order';
  const width = 840, margin = 44, usable = width - margin * 2;
  const font = (size, bold) => `${bold ? 700 : 400} ${size}px Arial, sans-serif`;
  function wrap(ctx, text, available, size, bold) {
    ctx.font = font(size, bold);
    const lines = [];
    let line = '';
    for (const word of String(text).split(/\s+/)) {
      const candidate = line ? `${line} ${word}` : word;
      if (ctx.measureText(candidate).width <= available) { line = candidate; continue; }
      if (line) { lines.push(line); line = ''; }
      const parts = typeof Intl.Segmenter === 'function'
        ? [...new Intl.Segmenter('bn', { granularity: 'grapheme' }).segment(word)].map(p => p.segment)
        : Array.from(word);
      for (const part of parts) {
        if (line && ctx.measureText(line + part).width > available) { lines.push(line); line = ''; }
        line += part;
      }
    }
    if (line) lines.push(line);
    return lines;
  }
  function drawText(ctx, text, x, y, available, size = 24, bold = false) {
    const lines = wrap(ctx, text, available, size, bold);
    ctx.fillStyle = '#142b30'; ctx.font = font(size, bold); ctx.textBaseline = 'top';
    for (const line of lines) { ctx.fillText(line, x, y); y += Math.ceil(size * 1.5); }
    return y;
  }
  function cover(ctx, image, x, y, w, h) {
    const ratio = Math.max(w / image.naturalWidth, h / image.naturalHeight);
    const sw = w / ratio, sh = h / ratio;
    ctx.drawImage(image, (image.naturalWidth - sw) / 2, (image.naturalHeight - sh) / 2, sw, sh, x, y, w, h);
  }
  async function png(canvas, name) {
    const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
    if (!blob) throw new Error('png');
    return new File([blob], name, { type: 'image/png' });
  }
  async function prepare() {
    shareButton.disabled = true;
    status.textContent = 'ইনভয়েস ও পণ্যের ছবি তৈরি হচ্ছে…';
    try {
      if (document.fonts?.ready) await document.fonts.ready;
      const cache = new Map();
      const load = src => {
        if (!cache.has(src)) cache.set(src, (async () => {
          const response = await fetch(src, { credentials: 'same-origin' });
          if (!response.ok) throw new Error('photo');
          const url = URL.createObjectURL(await response.blob());
          try { const image = new Image(); image.src = url; await image.decode(); return image; }
          finally { URL.revokeObjectURL(url); }
        })());
        return cache.get(src);
      };
      const images = await Promise.all(data.lines.map(line => load(line.photo)));
      const result = [];
      const pages = [];
      let canvas, ctx, y;
      function page() {
        canvas = document.createElement('canvas'); canvas.width = width; canvas.height = 1800;
        ctx = canvas.getContext('2d'); ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, width, 1800);
        y = drawText(ctx, 'FOOTSTEP ONLINE', margin, margin, usable, 36, true);
        y = drawText(ctx, `Invoice #${data.order}`, margin, y + 6, usable, 24, true);
        y = drawText(ctx, data.date, margin, y, usable, 20) + 18;
      }
      function finish() {
        const cropped = document.createElement('canvas'); cropped.width = width; cropped.height = Math.ceil(y + margin);
        cropped.getContext('2d').drawImage(canvas, 0, 0); pages.push(cropped);
      }
      function room(height) { if (y + height > 1700) { finish(); page(); } }
      function rule() {
        ctx.strokeStyle = '#d8e2df'; ctx.lineWidth = 2; ctx.beginPath(); ctx.moveTo(margin, y); ctx.lineTo(width - margin, y); ctx.stroke(); y += 20;
      }
      function text(value, size = 24, bold = false) {
        const lines = wrap(ctx, value, usable, size, bold);
        for (const line of lines) { room(size * 1.5); y = drawText(ctx, line, margin, y, usable, size, bold); }
      }
      page(); rule();
      for (const value of data.customer) text(value);
      y += 14;
      text(`Order status: ${data.status} · Payment: ${data.payment}`, 21);
      text(`Method: ${data.method}`, 21); y += 20; rule();
      for (let i = 0; i < data.lines.length; i++) {
        const line = data.lines[i];
        const x = margin + 140, available = usable - 140;
        const nameLines = wrap(ctx, line.name, available, 23, true);
        const sizeLines = wrap(ctx, `Size: ${line.size} · Qty: ${line.quantity}`, available, 28, true);
        const costLines = wrap(ctx, `Unit: ${line.unitPrice} · Total: ${line.total} ${data.currency}`, available, 21, false);
        const height = Math.max(150, nameLines.length * 35 + sizeLines.length * 42 + costLines.length * 32) + 22;
        room(height);
        const top = y;
        cover(ctx, images[i], margin, y, 112, 150);
        let end = drawText(ctx, line.name, x, y, available, 23, true);
        end = drawText(ctx, `Size: ${line.size} · Qty: ${line.quantity}`, x, end + 4, available, 28, true);
        end = drawText(ctx, `Unit: ${line.unitPrice} · Total: ${line.total} ${data.currency}`, x, end, available, 21);
        y = Math.max(top + 150, end) + 20; rule();
      }
      for (const addon of data.addons) {
        text(`${addon.name} · Qty: ${addon.quantity} · ${addon.total} ${data.currency}`, 23, true);
        if (addon.free) text(`Free gift / Offer discount: ${addon.saving} ${data.currency}`, 21);
        y += 10;
      }
      room(300); rule();
      for (const [key, value] of Object.entries(data.totals)) text(`${key}: ${value} ${data.currency}`, key === 'Total' ? 32 : 24, key === 'Total');
      y += 20;
      if (data.method === 'cash on delivery' && data.payment.toLowerCase() !== 'paid') text('Cash on Delivery — পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন।', 21);
      text('Thank you for choosing Footstep.', 21);
      finish();
      for (let i = 0; i < pages.length; i++) result.push(await png(pages[i], `Invoice-${safeOrder}-${i + 1}.png`));
      // Separate product image cards keep the first gallery photo and ordered size together.
      for (let i = 0; i < data.lines.length; i++) {
        const line = data.lines[i];
        const card = document.createElement('canvas'); card.width = width;
        const measure = card.getContext('2d');
        const nameHeight = wrap(measure, line.name, usable, 25, true).length * 38;
        const sizeHeight = wrap(measure, `Size: ${line.size} · Qty: ${line.quantity}`, usable, 38, true).length * 57;
        card.height = 1120 + nameHeight + sizeHeight;
        const c = card.getContext('2d'); c.fillStyle = '#fff'; c.fillRect(0, 0, width, card.height);
        let top = drawText(c, `Order #${data.order}`, margin, 28, usable, 23, true);
        // Contain the original photo, retaining the entire product.
        const image = images[i], scale = Math.min(usable / image.naturalWidth, 920 / image.naturalHeight);
        const w = image.naturalWidth * scale, h = image.naturalHeight * scale;
        c.drawImage(image, (width - w) / 2, top + 20 + (920 - h) / 2, w, h);
        top += 960;
        top = drawText(c, line.name, margin, top, usable, 25, true);
        drawText(c, `Size: ${line.size} · Qty: ${line.quantity}`, margin, top + 12, usable, 38, true);
        result.push(await png(card, `Product-${safeOrder}-${i + 1}.png`));
      }
      files = result;
      list.replaceChildren();
      urls.splice(0).forEach(url => URL.revokeObjectURL(url));
      for (const file of files) {
        const url = URL.createObjectURL(file); urls.push(url);
        const link = document.createElement('a'); link.href = url; link.download = file.name;
        link.className = 'btn btn-light mr-2 mb-2'; link.textContent = file.name.startsWith('Invoice') ? 'ইনভয়েস ছবি Download' : 'পণ্য ও সাইজ ছবি Download';
        list.append(link);
      }
      status.textContent = `${files.length}টি ছবি প্রস্তুত। WhatsApp বেছে সঠিক কাস্টমার নির্বাচন করুন; আগের মেসেজটিও সঙ্গে থাকবে।`;
      shareButton.textContent = 'WhatsApp-এ ইনভয়েস ও পণ্যের ছবি পাঠান ↗';
      shareButton.disabled = false;
    } catch (_) {
      files = [];
      status.textContent = 'একটি ছবি লোড হয়নি। সংযোগ দেখে আবার চেষ্টা করুন। আগের text message পাঠানোর বাটন নিচে আছে।';
      shareButton.textContent = 'ছবি আবার তৈরি করুন'; shareButton.disabled = false;
    }
  }
  shareButton.addEventListener('click', () => {
    if (!files.length) { prepare(); return; }
    if (!navigator.share || !navigator.canShare?.({ files })) {
      status.textContent = 'এই browser-এ একসঙ্গে ছবি শেয়ার সমর্থিত নয়। ছবি Download করুন, আগের WhatsApp message খুলে ছবিগুলো attach করুন।';
      return;
    }
    navigator.share({ files, text: message(), title: `Invoice #${data.order}` }).catch(error => {
      if (error.name !== 'AbortError') status.textContent = 'শেয়ার হয়নি। নিচের Download ও পুরোনো WhatsApp message বাটন ব্যবহার করতে পারেন।';
    });
  });
  prepare();
})();
