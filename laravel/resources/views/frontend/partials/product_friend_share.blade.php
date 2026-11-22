<div class="footstep-friend-share" id="footstep-friend-share" data-title="{{ $detailedProduct->getTranslation('name') }}" data-url="{{ route('product',$detailedProduct->slug) }}" data-price="{{ home_discounted_base_price($detailedProduct) }}">
<a class="footstep-share-whatsapp" href="https://wa.me/?text={{ rawurlencode($detailedProduct->getTranslation('name').' — '.home_discounted_base_price($detailedProduct).' '.route('product',$detailedProduct->slug)) }}" target="_blank" rel="noopener noreferrer" aria-label="Share product with friends on WhatsApp"><i class="lab la-whatsapp" aria-hidden="true"></i><span>WhatsApp</span></a>
<a class="footstep-share-facebook" href="https://www.facebook.com/sharer/sharer.php?u={{ rawurlencode(route('product',$detailedProduct->slug)) }}" target="_blank" rel="noopener noreferrer" aria-label="Share product on Facebook"><i class="lab la-facebook-f" aria-hidden="true"></i><span>Facebook</span></a>
<button type="button" class="footstep-share-messenger" data-native-share="Messenger" aria-label="Share product with friends using Messenger"><i class="lab la-facebook-messenger" aria-hidden="true"></i><span>Messenger</span></button>
<button type="button" class="footstep-share-instagram" data-native-share="Instagram" aria-label="Share product with friends using Instagram"><i class="lab la-instagram" aria-hidden="true"></i><span>Instagram</span></button>
<button type="button" class="footstep-share-more" data-native-share="More apps" aria-label="More ways to share product"><i class="las la-share-alt" aria-hidden="true"></i><span>More</span></button>
<p class="footstep-share-status" role="status" id="footstep-share-status"></p>
<div class="footstep-share-fallback" id="footstep-share-fallback" hidden><label for="footstep-share-link">Share this product link with your friend</label><input id="footstep-share-link" class="form-control" readonly value="{{ route('product',$detailedProduct->slug) }}"><button type="button" class="btn btn-sm btn-light" id="footstep-copy-share-link">Copy link</button></div>
</div>
<link rel="stylesheet" href="{{ static_asset('assets/css/product-friend-share.css') }}?v=1.6.9">
<script defer src="{{ static_asset('assets/js/product-friend-share.js') }}?v=1.6.9"></script>

