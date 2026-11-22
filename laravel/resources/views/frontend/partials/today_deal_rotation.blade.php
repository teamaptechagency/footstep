@php
 $todayItems=$todayProducts ?? \Illuminate\Support\Facades\Cache::remember('ops_today_v2_'.intdiv(time(),900),900,function(){return filter_products(\App\Models\Product::where('published',1)->where('todays_deal',1))->with(['stocks','taxes','product_translations'])->inRandomOrder()->limit(20)->get();});
@endphp
@if($todayItems->isNotEmpty())
@once
@push('preload')<link rel="stylesheet" href="{{ static_asset('assets/css/home-refresh.css') }}?v=2">@endpush
@endonce
<section class="today-deals {{ !empty($todayCompact)?'today-deals-compact':'container my-4' }}" aria-label="Today's Deal">
 <header><div><small>FRESH PICKS</small><h2>Today's Deal <span>Hot</span></h2></div><i class="las la-bolt" aria-hidden="true"></i></header>
 <div class="today-deals-list" data-today-rotation>
 @foreach($todayItems as $product)
 <a class="today-deal-item" href="{{ route('product',$product->slug) }}" @if($loop->index>=4) hidden @endif>
  <img src="{{ \App\Services\OptimizedImages::asset($product->thumbnail_img) }}" alt="" width="54" height="66" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">
  <div><span class="today-deal-name">{{ $product->getTranslation('name') }}</span><strong>{{ home_discounted_base_price($product) }}</strong>@if(home_base_price($product,false)!=home_discounted_base_price($product,false))<del>{{ home_base_price($product) }}</del>@endif</div>
 </a>
 @endforeach
 </div>
</section>
@endif
