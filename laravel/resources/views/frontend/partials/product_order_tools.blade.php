@php
    $contactNumber=trim((string)get_setting('contact_phone'));
    if(preg_match('~(?:wa\.me/|phone=)([+0-9]+)~',$contactNumber,$match))$contactNumber=$match[1];
    $contactNumber=preg_replace('/[^0-9]/','',$contactNumber);
    if(substr($contactNumber,0,2)==='00')$contactNumber=substr($contactNumber,2);
    if(preg_match('/^01[3-9][0-9]{8}$/D',$contactNumber))$contactNumber='88'.$contactNumber;
    if(!preg_match('/^[1-9][0-9]{6,14}$/D',$contactNumber))$contactNumber='';
    $facebookUrl=(string)get_setting('facebook_link');
    $messengerPage='footstep11';
    $facebookHost=strtolower((string)parse_url($facebookUrl,PHP_URL_HOST));
    if(in_array($facebookHost,['facebook.com','www.facebook.com','m.facebook.com','m.me'],true)){
        $facebookPath=trim((string)parse_url($facebookUrl,PHP_URL_PATH),'/');
        parse_str((string)parse_url($facebookUrl,PHP_URL_QUERY),$facebookQuery);
        if($facebookPath==='profile.php' && preg_match('/^[0-9]+$/D',(string)($facebookQuery['id']??'')))$messengerPage=$facebookQuery['id'];
        elseif(preg_match('/^[a-zA-Z0-9.]+$/D',$facebookPath)&&!in_array($facebookPath,['profile.php','pages','share','sharer.php'],true))$messengerPage=$facebookPath;
    }
@endphp
<link rel="stylesheet" href="{{ static_asset('assets/css/product-order-tools.css') }}?v=2.0.18">
<div id="footstep-product-tools" data-name="{{ $detailedProduct->getTranslation('name') }}" data-id="{{ $detailedProduct->id }}" data-url="{{ route('product',$detailedProduct->slug) }}" data-price="{{ home_discounted_base_price($detailedProduct) }}" data-whatsapp="{{ $contactNumber }}" data-messenger="{{ $messengerPage }}">
    <aside class="footstep-sticky-order" id="footstep-sticky-order" aria-label="Product checkout" hidden>
        <div class="container footstep-sticky-inner"><img src="{{ \App\Services\OptimizedImages::asset($detailedProduct->thumbnail_img) }}" alt="" width="46" height="46" decoding="async" srcset="{{ \App\Services\OptimizedImages::srcset($detailedProduct->thumbnail_img) }}" sizes="46px"><div class="footstep-sticky-copy"><span class="footstep-sticky-name">{{ $detailedProduct->getTranslation('name') }}</span><span class="footstep-sticky-price" id="footstep-sticky-price">{{ home_discounted_base_price($detailedProduct) }}</span></div><button type="button" id="footstep-sticky-checkout" class="btn btn-primary"><i class="las la-shopping-bag" aria-hidden="true"></i> <span>Order Now</span></button></div>
    </aside>
</div>
<script src="{{ static_asset('assets/js/product-order-tools.js') }}?v=2.0.18" defer></script>

@include('frontend.partials.direct_checkout')
