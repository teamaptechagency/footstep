@php($studioName=$product->getTranslation('name'))
<article class="studio-product">
    <div class="studio-product-media"><a href="{{ route('product', ['slug'=>$product->slug,'flash_deal'=>$flashDealContext ?? null]) }}" tabindex="-1" aria-hidden="true"><img src="{{ \App\Services\OptimizedImages::asset($product->thumbnail_img) }}" alt="" loading="lazy" decoding="async" width="480" height="520" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';" srcset="{{ \App\Services\OptimizedImages::srcset($product->thumbnail_img) }}" sizes="(max-width:575px) 46vw,(max-width:991px) 30vw,280px"></a>
    @if(discount_in_percentage($product)>0)<span class="studio-discount">−{{ discount_in_percentage($product) }}%</span>@endif
    <button class="studio-heart" type="button" onclick="addToWishList({{ $product->id }})" aria-label="Add {{ $studioName }} to wishlist"><i class="lar la-heart" aria-hidden="true"></i></button>
    <button class="studio-compare" type="button" onclick="addToCompare({{ $product->id }})" aria-label="Add {{ $studioName }} to compare"><i class="las la-exchange-alt" aria-hidden="true"></i></button>
    @include('frontend.partials.gallery_order',['galleryOrderClass'=>'studio-quick'])</div>
    <div class="studio-product-meta"><span>FOOTSTEP COLLECTION</span><i class="las la-arrow-up" aria-hidden="true"></i></div>
    <h3><a href="{{ route('product', ['slug'=>$product->slug,'flash_deal'=>$flashDealContext ?? null]) }}">{{ $studioName }}</a></h3>
    <div class="studio-price"><strong>{{ home_discounted_base_price($product) }}</strong>@if(home_base_price($product,false)!=home_discounted_base_price($product,false))<del>{{ home_base_price($product) }}</del>@endif</div>
</article>
