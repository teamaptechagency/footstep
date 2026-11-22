@php


 $saleNow=time();$saleActive=$flash_deal->status==1&&$saleNow>=$flash_deal->start_date&&$saleNow<=$flash_deal->end_date;


 $saleUpcoming=$flash_deal->status==1&&$saleNow<$flash_deal->start_date;


 $saleIds=$flash_deal->flash_deal_products->pluck('product_id')->unique();


 $saleById=filter_products(\App\Models\Product::where('published',1)->whereIn('id',$saleIds))->with('stocks')->get()->keyBy('id');


 $saleItems=$saleIds->map(function($id)use($saleById){return $saleById->get($id);})->filter()->values();


 $saleDiscount=(int)$saleItems->map(function($p){return discount_in_percentage($p);})->max();


 $saleEnd=$saleUpcoming?$flash_deal->start_date:$flash_deal->end_date;


@endphp


<link rel="stylesheet" href="{{ static_asset('assets/css/flash-collection.css') }}?v=mobile8">


<div class="fsale" id="flash-collection" data-compact-header="{{ $flash_deal->slug === 'adidas-terrex-3y3qt' ? '1' : '0' }}" data-end="{{ $saleEnd }}" data-active="{{ $saleActive?'1':'0' }}">


<div class="container">


<nav class="fsale-crumb" aria-label="Breadcrumb"><a href="{{ route('home') }}">Home</a><span aria-hidden="true">/</span><a href="{{ route('flash-deals') }}">Flash deals</a><span aria-hidden="true">/</span><span>Selected collection</span></nav>


<section class="fsale-hero" aria-labelledby="sale-title"><div class="fsale-copy"><span class="fsale-kicker"><span></span> FOOTSTEP / THE FLASH EDIT</span><h1 id="sale-title">{{ $flash_deal->getTranslation('title') }}</h1><p class="fsale-intro">আপনার পরের পছন্দের জুতা,<br><strong>এবার আরও ভালো দামে।</strong></p><div class="fsale-hero-bottom"><a class="fsale-explore" href="#sale-products">Explore the collection <i class="las la-arrow-down" aria-hidden="true"></i></a><span>{{ str_pad((string)$saleItems->count(),2,'0',STR_PAD_LEFT) }} selected styles</span></div></div>


<div class="fsale-art" aria-hidden="true"><span class="fsale-art-word">STEP<br>INTO<br>MORE.</span>@foreach($saleItems->take(3) as $item)<div class="fsale-photo fsale-photo-{{ $loop->iteration }}"><img src="{{ \App\Services\OptimizedImages::asset($item->thumbnail_img) }}" alt="" loading="lazy" decoding="async"></div>@endforeach @if($saleDiscount>0)<div class="fsale-off"><small>UP TO</small><strong>{{ $saleDiscount }}<span>%</span></strong><small>OFF THIS EDIT</small></div>@endif</div></section>


<div class="fsale-info"><div class="fsale-clock-label"><i class="las la-bolt" aria-hidden="true"></i><div><strong data-sale-state>{{ $saleActive?'This edit won’t last forever.':($saleUpcoming?'Something good is on the way.':'This flash edit has ended.') }}</strong><small>{{ $saleUpcoming?'Offer starts in / অফার শুরু হতে বাকি':'Offer ends in / অফার শেষ হতে বাকি' }}</small></div></div><div class="fsale-clock" role="timer" aria-label="24-hour countdown cycle" title="২৪ ঘণ্টার চক্র; অফারের মূল শেষ সময় বদলায় না"><div><b data-unit="hours">—</b><small>Hours</small></div><span>:</span><div><b data-unit="minutes">—</b><small>Min</small></div><span>:</span><div><b data-unit="seconds">—</b><small>Sec</small></div></div></div>


@if($saleActive)


<section id="sale-products" class="fsale-products" aria-label="Collection products">


<div class="fsale-grid">@foreach($saleItems as $product)<article class="fsale-card aiz-card-box" data-sale-name="{{ mb_strtolower($product->getTranslation('name')) }}"><a class="fsale-card-image" href="{{ route('product',['slug'=>$product->slug,'flash_deal'=>$flash_deal->id]) }}"><img src="{{ \App\Services\OptimizedImages::asset($product->thumbnail_img) }}" srcset="{{ \App\Services\OptimizedImages::srcset($product->thumbnail_img) }}" sizes="(max-width:575px) 46vw,(max-width:991px) 44vw,360px" loading="{{ $loop->index<2?'eager':'lazy' }}" fetchpriority="{{ $loop->first?'high':'auto' }}" decoding="async" alt="{{ $product->getTranslation('name') }}">@if(discount_in_percentage($product)>0)<span class="fsale-tag">−{{ discount_in_percentage($product) }}%</span>@endif<span class="fsale-view">View details <i class="las la-arrow-up" aria-hidden="true"></i></span></a><div class="fsale-card-body"><span class="fsale-card-eyebrow">THE FLASH COLLECTION</span><h3><a href="{{ route('product',['slug'=>$product->slug,'flash_deal'=>$flash_deal->id]) }}">{{ $product->getTranslation('name') }}</a></h3><div class="fsale-price"><strong>{{ home_discounted_base_price($product) }}</strong>@if(home_base_price($product)!=home_discounted_base_price($product))<del>{{ home_base_price($product) }}</del>@endif</div><div class="fsale-card-footer"><span>Select your size at checkout</span>@include('frontend.partials.gallery_order',['flashDealContext'=>$flash_deal->id,'galleryOrderClass'=>'fsale-order'])</div></div></article>@endforeach</div>


<p class="fsale-empty" id="sale-empty" @if($saleItems->count())hidden @endif>No products are currently available in this collection.</p><p class="fsale-note"><i class="las la-info-circle" aria-hidden="true"></i> Product-এর বিস্তারিত জানতে ছবিতে চাপুন। Order Now থেকে size বেছে অর্ডার করুন।</p></section>


@else<section class="fsale-ended"><h2>{{ $saleUpcoming?'The collection is almost here.':'Missed this edit?' }}</h2><p>{{ $saleUpcoming?'অফার শুরু হলে এখানে order করতে পারবেন।':'আরও পছন্দের জুতা খুঁজে নিন আমাদের collection-এ।' }}</p><a class="fsale-explore" href="{{ route('search') }}">Explore all products <i class="las la-arrow-right" aria-hidden="true"></i></a></section>@endif


<div class="fsale-bottom"><span>GOOD PAIRS. BETTER PRICES.</span><a href="{{ route('search') }}">Keep exploring <i class="las la-arrow-right" aria-hidden="true"></i></a></div>


</div></div><script src="{{ static_asset('assets/js/flash-collection.js') }}?v=mobile8" defer></script>


