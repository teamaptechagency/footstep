(() => {
  'use strict';
  const label = document.querySelector('.label');
  const button = document.querySelector('#share-label');
  const status = document.querySelector('#share-status');
  const fallback = document.querySelector('#share-fallback');
  if (!label || !button) return;
  const text = selector => label.querySelector(selector)?.textContent.trim() || '—';
  const caption = () => `${text('.courier-name')} · ${text('.account-name')}\nCustomer: ${text('.phone')}\nConsignment ID: ${text('.cid')}\n${text('.shipment-invoice')}`;
  let file;
  const say = message => { status.textContent = message; };

  async function prepare() {
    button.disabled = true;
    say('লেবেলের ছবি তৈরি হচ্ছে…');
    try {
      if (document.fonts?.ready) await document.fonts.ready;
      const sizeRows = [...label.querySelectorAll('.ordered-size')];
      const rows = await Promise.all([...label.querySelectorAll('.product-photos .item')].map(async (item, index) => {
        const image = item.querySelector('img');
        let bitmap;
        if (image) {
          const response = await fetch(image.src, { credentials: 'same-origin' });
          if (!response.ok) throw new Error('image');
          const blob = await response.blob();
          const url = URL.createObjectURL(blob);
          try {
            bitmap = new Image();
            bitmap.src = url;
            await bitmap.decode();
          } finally { URL.revokeObjectURL(url); }
        }
        return { bitmap, size: sizeRows[index].querySelector('.item-size').textContent.trim(), qty: sizeRows[index].querySelector('.item-qty').textContent.trim() };
      }));
      // Render a private PNG locally. Nothing is uploaded to an image service.
      const canvas = document.createElement('canvas');
      const width = 760, margin = 46, content = width - margin * 2;
      canvas.width = width;
      const ctx = canvas.getContext('2d');
      let y = margin;
      const ops = [];
      function words(value, x, available, size = 28, bold = false) {
        ctx.font = `${bold ? '700' : '400'} ${size}px Arial, sans-serif`;
        const lines = [];
        let line = '';
        const pieces = value.split(/\s+/);
        for (const word of pieces) {
          const candidate = line ? `${line} ${word}` : word;
          if (ctx.measureText(candidate).width <= available) { line = candidate; continue; }
          if (line) { lines.push(line); line = ''; }
          const segments = typeof Intl.Segmenter === 'function'
            ? [...new Intl.Segmenter('bn', { granularity: 'grapheme' }).segment(word)].map(s => s.segment)
            : Array.from(word);
          for (const part of segments) {
            if (line && ctx.measureText(line + part).width > available) { lines.push(line); line = ''; }
            line += part;
          }
        }
        if (line) lines.push(line);
        for (const value of lines) {
          const top = y;
          ops.push(c => { c.fillStyle = '#142b30'; c.font = `${bold ? '700' : '400'} ${size}px Arial, sans-serif`; c.fillText(value, x, top); });
          y += Math.ceil(size * 1.5);
        }
      }
      function rule() {
        const top = y;
        ops.push(c => { c.strokeStyle = '#d8e2df'; c.lineWidth = 2; c.beginPath(); c.moveTo(margin, top); c.lineTo(width - margin, top); c.stroke(); });
        y += 22;
      }
      const headerTop = y, leftWidth = 270, headerGap = 26;
      words(text('.courier-name'), margin, leftWidth, 44, true);
      const courierBottom = y;
      y = headerTop;
      words(text('.account-name'), margin + leftWidth + headerGap, content - leftWidth - headerGap, 34, true);
      y = Math.max(y, courierBottom);
      y += 8; rule();
      if (label.querySelector('.mock')) words(text('.mock'), margin, content, 28, true);
      words('CONSIGNMENT ID', margin, content, 21);
      words(text('.cid'), margin, content, 52, true);
      label.querySelectorAll('.shipment-details > div').forEach(el => words(el.textContent.trim(), margin, content, 26));
      y += 16; rule();
      const bodyTop = y, photoWidth = 252, photoHeight = 336;
      const detailX = margin + photoWidth + 28, detailWidth = width - margin - detailX;
      y = bodyTop;
      words(text('.recipient-name'), detailX, detailWidth, 30, true);
      y += 10;
      words(text('.phone'), detailX, detailWidth, 36, true);
      y += 10;
      words(text('.recipient-address'), detailX, detailWidth, 26);
      y += 28;
      for (let i = 0; i < rows.length; i++) {
        if (rows.length > 1) words('#' + (i + 1), detailX, detailWidth, 18, true);
        words(rows[i].size, detailX, detailWidth, 56, true);
        words(rows[i].qty, detailX, detailWidth, 28);
        y += 18;
      }
      const detailsBottom = y;
      const fittedPhotoHeight = Math.max(photoHeight, (y - bodyTop - rows.length * 38) / Math.max(1, rows.length));
      for (let i = 0; i < rows.length; i++) {
        const row = rows[i], top = bodyTop + i * (fittedPhotoHeight + 38);
        if (rows.length > 1) { y = top; words('#' + (i + 1), margin, photoWidth, 18, true); }
        const imageTop = top + (rows.length > 1 ? 28 : 0);
        if (row.bitmap) {
          const image = row.bitmap;
          ops.push(c => {
            const ratio = Math.min(photoWidth / image.naturalWidth, fittedPhotoHeight / image.naturalHeight);
            const drawnWidth = image.naturalWidth * ratio, drawnHeight = image.naturalHeight * ratio;
            c.drawImage(image, margin + (photoWidth - drawnWidth) / 2, imageTop, drawnWidth, drawnHeight);
          });
        } else {
          ops.push(c => { c.fillStyle = '#eef3f2'; c.fillRect(margin, imageTop, photoWidth, fittedPhotoHeight); c.fillStyle = '#58706a'; c.font = '24px Arial'; c.fillText('No image', margin + 60, imageTop + 140); });
        }
      }
      y = Math.max(detailsBottom, bodyTop + rows.length * (fittedPhotoHeight + 38)) + 20;
      rule();
      words(text('.foot'), margin, content, 20);
      canvas.height = Math.ceil(y + margin);
      ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, width, canvas.height);
      ctx.textBaseline = 'top';
      ops.forEach(draw => draw(ctx));
      const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/png'));
      if (!blob) throw new Error('png');
      const id = text('.cid').replace(/[^a-z0-9_-]/gi, '').slice(0, 64) || 'label';
      file = new File([blob], `${text('.courier-name')}-${text('.account-name')}-${id}.png`, { type: 'image/png' });
      button.disabled = false;
      say('বাটন চাপুন → WhatsApp বেছে নিন → যাকে পাঠাবেন তাকে নির্বাচন করুন।');
      fallback.hidden = Boolean(navigator.canShare?.({ files: [file] }));
    } catch (_) {
      file = undefined;
      button.disabled = false;
      button.textContent = 'ছবি আবার তৈরি করুন';
      say('ছবি তৈরি হয়নি। ইন্টারনেট সংযোগ দেখে আবার চাপুন।');
    }
  }
  button.addEventListener('click', () => {
    if (!file) { prepare(); return; }
    if (!navigator.canShare?.({ files: [file] }) || !navigator.share) {
      fallback.hidden = false;
      say('এই browser-এ ছবি সরাসরি শেয়ার করা যাচ্ছে না। ছবি নামিয়ে ও নিচের তথ্য copy করে WhatsApp-এ পাঠান।');
      return;
    }
    // Keep native share in the click gesture; PNG generation finishes beforehand.
    navigator.share({ files: [file], text: caption(), title: `${text('.courier-name')} · ${text('.account-name')}` }).catch(error => {
      if (error.name === 'AbortError') return;
      fallback.hidden = false;
      say('শেয়ার হয়নি। ছবি download এবং নম্বর/ID copy করে WhatsApp-এ পাঠাতে পারেন।');
    });
  });
  document.querySelector('#download-label').addEventListener('click', () => {
    if (!file) return;
    const url = URL.createObjectURL(file), link = document.createElement('a');
    link.href = url; link.download = file.name; link.click();
    setTimeout(() => URL.revokeObjectURL(url), 60000);
  });
  document.querySelector('#copy-label-text').addEventListener('click', async () => {
    try { await navigator.clipboard.writeText(caption()); say('কাস্টমারের নম্বর ও courier ID copy হয়েছে।'); }
    catch (_) { say('নিচের টেক্সট select করে copy করুন।'); }
  });
  document.querySelector('#share-caption').textContent = caption();
  prepare();
})();
