<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0;font:14px Arial,sans-serif}.footstep-direct-dialog{width:100%!important;max-width:100%!important;max-height:100dvh!important;margin:0!important;border-radius:0!important}input,textarea,select{box-sizing:border-box;width:100%;padding:12px;border:1px solid #dbe3df;border-radius:10px}.direct-delivery input{width:auto}.form-control{margin-bottom:12px}.alert-danger{color:#a21}.btn{padding:10px}dialog::backdrop{background:white}</style></head><body><form id="option-choice-form" data-quick="1" data-initial-quote="{{ json_encode($initialQuote??null) }}" hidden><input name="quantity" value="{{ $initialQuote['quantity']??max(1,(int)$detailedProduct->min_qty) }}"><input name="stock_id" value="{{ $initialQuote['stock_id']??'' }}"></form>
@include('frontend.partials.direct_checkout')
<script>
window.addEventListener('DOMContentLoaded', function () {
  const prepared = @json(request()->boolean('prepare'));
  const dialog = document.getElementById('footstep-direct-dialog');
  const origin = location.origin;
  let activated = false;
  const activate = () => {
    if (activated) return;
    activated = true;
    window.footstepDirectCheckout();
    if (parent !== window) parent.postMessage({type:'footstep-checkout-ready'}, origin);
    if (prepared) {
      const tracking = new URL(location.href);
      tracking.searchParams.delete('prepare');
      tracking.searchParams.set('checkout_meta', '1');
      fetch(tracking, {headers:{Accept:'application/json'}, cache:'no-store'})
        .then(response => response.ok ? response.json() : null)
        .then(payload => {
          if (!payload?.config?.enabled) return;
          const asset = new URL(payload.script, origin);
          if (asset.origin !== origin || !asset.pathname.endsWith('/assets/js/meta-pixel.js')) return;
          window.FootstepMetaConfig = payload.config;
          window.FootstepMetaEvents = payload.events;
          const script = document.createElement('script');
          script.src = asset.href;
          document.head.appendChild(script);
        }).catch(() => {});
    }
  };
  dialog.addEventListener('close', function () {
    if (parent !== window) {
      parent.postMessage({type:'footstep-checkout-close'}, origin);
      return;
    }
    if (document.referrer && new URL(document.referrer).origin === origin) {
      history.back();
      return;
    }
    location.replace('/');
  });
  if (prepared && parent !== window) {
    window.addEventListener('message', event => {
      if (event.origin === origin && event.source === parent && event.data?.type === 'footstep-checkout-activate') activate();
    });
    parent.postMessage({type:'footstep-checkout-prepared'}, origin);
  } else {
    activate();
  }
});
</script>
@if(!request()->boolean('prepare'))
@include('frontend.partials.meta_pixel')
@endif
</body></html>
