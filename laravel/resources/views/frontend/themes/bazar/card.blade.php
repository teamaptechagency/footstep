@php($bazarName=$product->getTranslation('name'))
<article class="bazar-product {{ !empty($bazarShowBuy)?'bazar-horizontal-product':'' }}">
<div class="bazar-product-media"><a href="{{ route('product',['slug'=>$product->slug,'flash_deal'=>$flashDealContext??null]) }}" tabindex="-1" aria-hidden="true"><img src="{{ \App\Services\OptimizedImages::asset($product->thumbnail_img) }}" alt="" width="400" height="400" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';" srcset="{{ \App\Services\OptimizedImages::srcset($product->thumbnail_img) }}" sizes="(max-width:575px) 46vw,(max-width:991px) 30vw,280px"></a>
@if(discount_in_percentage($product)>0)<span class="bazar-discount">{{ discount_in_percentage($product) }}% OFF</span>@endif
<button class="bazar-heart" type="button" onclick="addToWishList({{ $product->id }})" aria-label="Add {{ $bazarName }} to wishlist"><i class="lar la-heart" aria-hidden="true"></i></button></div>
<div class="bazar-product-body"><h3><a href="{{ route('product',['slug'=>$product->slug,'flash_deal'=>$flashDealContext??null]) }}">{{ $bazarName }}</a></h3><div class="bazar-price"><strong>{{ home_discounted_base_price($product) }}</strong>@if(home_base_price($product,false)!=home_discounted_base_price($product,false))<del>{{ home_base_price($product) }}</del>@endif</div>
<div class="bazar-card-actions">@include('frontend.partials.gallery_order',['galleryOrderClass'=>'bazar-card-cart'])</div></div>
</article>
