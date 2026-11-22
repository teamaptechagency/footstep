@php
 $galleryOptions=json_decode($product->choice_options ?? '[]',true) ?: [];
 $galleryHasOptions=count($galleryOptions)>0 || (int)$product->colors===1 || count(json_decode($product->colors ?? '[]',true) ?: [])>0;
 $galleryClass=$galleryOrderClass ?? 'btn btn-primary btn-block mt-2';
 $galleryUrl=route('product',['slug'=>$product->slug,'flash_deal'=>$flashDealContext??null]);
@endphp
@if($product->external_link)<a class="{{ $galleryClass }}" href="{{ $product->external_link }}" rel="noopener">Order Now</a>@else
<button type="button" class="{{ $galleryClass }}" data-card-checkout="{{ route('checkout.quick',$product->id) }}" data-product-url="{{ $galleryUrl }}"><i class="las la-shopping-bag" aria-hidden="true"></i> Order Now</button>
@endif
