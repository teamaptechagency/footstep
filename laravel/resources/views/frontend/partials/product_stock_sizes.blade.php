<script id="product-stock-data" type="application/json">@json($detailedProduct->stocks->map(function($s){return ['variant'=>$s->variant,'qty'=>(int)$s->qty];})->values())</script>
<style>#option-choice-form .stock-unavailable{opacity:.35;cursor:not-allowed}#option-choice-form .stock-unavailable .aiz-megabox-elem{text-decoration:line-through;background:#f3f4f6}</style>
<script src="{{ static_asset('assets/js/product-stock-sizes.js') }}?v=2.2.6"></script>
