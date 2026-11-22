@php
 $directDraftToken=bin2hex(random_bytes(16));
 \App\Services\CheckoutDrafts::owner(request());
 $directDefaults=\App\Services\OnePageCheckout::defaults(Auth::user());
 $directAddons=\App\Services\ProductAddons::available($detailedProduct->id);
 $directExitOffer=\App\Services\CheckoutExitOffer::issue($detailedProduct->id,$directDraftToken,$directAddons);
@endphp
<dialog id="footstep-direct-dialog" class="footstep-direct-dialog" aria-labelledby="direct-title" data-quote="{{ route('checkout.direct.quote') }}" data-submit="{{ route('checkout.direct.store') }}" data-product-name="{{ $detailedProduct->getTranslation('name') }}" data-product-url="{{ parse_url(route('product', $detailedProduct->slug), PHP_URL_PATH) }}" data-whatsapp="{{ \App\Services\OrderConfirmationWhatsApp::phone(get_setting('order_confirmation_whatsapp') ?: get_setting('contact_phone')) }}" data-member="{{ Auth::check()?'1':'0' }}">
 <form data-checkout-draft="direct" data-draft-url="{{ route('checkout.draft') }}" id="footstep-direct-form" data-meta-payment>
 @csrf<input type="hidden" name="exit_offer_token" value=""><input type="hidden" name="product_id" value="{{ $detailedProduct->id }}"><input type="hidden" name="quantity" value="1"><input type="hidden" name="checkout_token" value="{{ $directDraftToken }}"><input type="hidden" name="checkout_draft_token" value="{{ $directDraftToken }}">
 <div class="footstep-direct-head"><h2 id="direct-title"><i class="las la-money-bill-wave" aria-hidden="true"></i> ক্যাশ অন ডেলিভারিতে অর্ডার করুন</h2><button type="button" data-direct-close aria-label="Close checkout">×</button></div>
 <div class="footstep-direct-product"><img src="{{ \App\Services\OptimizedImages::asset($detailedProduct->thumbnail_img) }}" alt="" width="48" height="48" srcset="{{ \App\Services\OptimizedImages::srcset($detailedProduct->thumbnail_img) }}" sizes="48px" decoding="async"><div><strong>{{ $detailedProduct->getTranslation('name') }}</strong><small id="direct-quantity"></small></div><div class="direct-product-size"> <label for="direct-stock" class="direct-size-label">Size / সাইজ</label>
 <select class="direct-size-select" name="stock_id" id="direct-stock" required>
 @foreach($detailedProduct->stocks as $directStock)
 <option value="{{ $directStock->id }}" data-variant="{{ $directStock->variant }}" @if($directStock->qty < max(1,(int)$detailedProduct->min_qty)) disabled @endif>{{ $directStock->variant ?: 'Standard' }}{{ $directStock->qty < max(1,(int)$detailedProduct->min_qty) ? ' — Stock out' : '' }}</option>
 @endforeach
 </select></div></div>
 <div class="footstep-direct-body">
 @if(count($directAddons))<fieldset class="direct-addons"><legend>Add-ons / সঙ্গে নিতে পারেন</legend>@foreach($directAddons as $directAddon)<label><input type="checkbox" name="addon_ids[]" value="{{ $directAddon['id'] }}" @if(in_array($directAddon['id'],array_map('intval',request()->input('addon_ids',[])))) checked @endif><span>🧦 {{ $directAddon['name'] }} <b>+৳{{ number_format($directAddon['price'],2) }}</b>@if($directAddon['regular_price']!=$directAddon['price']) <del>৳{{ number_format($directAddon['regular_price'],2) }}</del>@endif</span></label>@endforeach<small>প্রতি product unit-এর সঙ্গে একটি করে; মোটের মধ্যে charge যোগ হবে।</small></fieldset>@endif
 <p id="direct-loading" role="status" aria-live="polite" hidden>মূল্য ও সাইজ যাচাই হচ্ছে… এর মধ্যে আপনার তথ্য পূরণ করতে পারেন।</p><button type="button" id="direct-retry" class="btn btn-light mb-2" hidden>আবার মূল্য/সাইজ যাচাই করুন</button>
 <div id="direct-error" class="alert alert-danger" role="alert" hidden></div>

 <label for="direct-name">আপনার নাম *</label><input class="form-control" id="direct-name" name="name" autocomplete="name" maxlength="191" required value="{{ $directDefaults['name'] }}" placeholder="আপনার সম্পূর্ণ নাম লিখুন">
 <label for="direct-phone">মোবাইল নম্বর *</label><input class="form-control" id="direct-phone" name="phone" type="tel" autocomplete="tel" maxlength="25" required value="{{ $directDefaults['phone'] }}" placeholder="01XXXXXXXXX">
 <fieldset class="direct-delivery"><legend>ডেলিভারি এলাকা *</legend><div>@foreach(\App\Services\OnePageCheckout::FEES as $area=>$fee)<label><input type="radio" name="city" value="{{ $area }}" data-fee="{{ $fee }}" required @if($directDefaults['city']===$area) checked @endif><span><i class="las la-truck" aria-hidden="true"></i><strong>{{ $area==='In Dhaka City'?'ঢাকার ভিতরে':'ঢাকার বাইরে' }}</strong><small>ডেলিভারি — ৳{{ $fee }}</small></span></label>@endforeach</div></fieldset>
 <label for="direct-address">সম্পূর্ণ ঠিকানা *</label><textarea class="form-control" id="direct-address" name="address" autocomplete="street-address" rows="2" maxlength="255" required placeholder="বাড়ি নং, রোড, এলাকা, জেলা…">{{ $directDefaults['address'] }}</textarea>
 <div class="direct-summary"><h3>অর্ডার সারাংশ</h3><div><span>পণ্যমূল্য</span><strong id="direct-subtotal">—</strong></div><div><span>ডেলিভারি চার্জ</span><strong id="direct-fee">এলাকা নির্বাচন করুন</strong></div><div class="direct-total"><span>মোট</span><strong id="direct-total">—</strong></div></div>
 @guest<small class="text-muted">এই browser-এ আপনার delivery তথ্য মনে রাখা হবে।</small>@endguest
 </div>
 <div class="footstep-direct-footer"><button id="direct-confirm" type="submit" disabled><i class="las la-shield-alt" aria-hidden="true"></i> অর্ডার নিশ্চিত করুন</button><small>পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন</small></div>
 </form>
 <section class="direct-exit-help" id="direct-exit-help" aria-labelledby="direct-exit-title" hidden>
  <div class="direct-exit-card">
   @if($directExitOffer)
   <div class="direct-free-offer" data-exit-offer-token="{{ $directExitOffer['token'] }}" data-exit-offer-ids="{{ json_encode(array_column($directExitOffer['addons'],'id')) }}">
    <span class="direct-free-tag"><i class="las la-gift" aria-hidden="true"></i> আপনার জন্য উপহার</span>
    <h3>অর্ডার করলেই {{ count($directExitOffer['addons'])===1 ? $directExitOffer['addons'][0]['name'] : 'add-on' }} ফ্রি!</h3>
    <p><strong>অফারটি শুধু এই পণ্যের এই মুহূর্তের অর্ডারের জন্য প্রযোজ্য।</strong></p>
    <button type="button" id="direct-exit-free-accept">উপহারসহ অর্ডার করুন →</button>
   </div>
   @endif
   <span class="direct-exit-icon" aria-hidden="true">?</span>
   <h2 id="direct-exit-title" tabindex="-1">অর্ডার করতে সমস্যা হচ্ছে?</h2>
   <p>WhatsApp-এ আমাদের জানান। পণ্যের তথ্য ও আপনার বাছাই করা সাইজসহ message তৈরি থাকবে।</p>
   <a id="direct-exit-whatsapp" target="_blank" rel="noopener noreferrer">WhatsApp-এ সাহায্য নিন ↗</a>
   <small>WhatsApp খুলে message-টি Send করুন। এতে অর্ডার জমা হবে না।</small>
   <button type="button" id="direct-exit-return">ফর্মে ফিরুন</button>
   <button type="button" id="direct-exit-dismiss">এখন বন্ধ করুন</button>
  </div>
 </section>
</dialog>
<link rel="stylesheet" href="{{ static_asset('assets/css/direct-checkout.css') }}?v=restored-original-20261113">
<script src="{{ static_asset('assets/js/direct-checkout.js') }}?v=delivery-20261110" defer></script>
<style>.direct-addons{border:1px solid #d9e7df;border-radius:10px;padding:12px;margin:5px 0 18px}.direct-addons legend{font-size:13px;font-weight:700}.direct-addons label{display:flex;align-items:center;gap:9px;font-size:13px;line-height:1.7;cursor:pointer}.direct-addons input{width:auto!important;flex-shrink:0}.direct-addons small{font-size:10px;color:#78887f}.direct-addons b{color:#008a48}.direct-addons del{color:#929b96;font-size:11px}.footstep-direct-body input:not([type=checkbox]):not([type=radio]),.footstep-direct-body textarea{font-size:16px}</style>

<script src="{{ static_asset('assets/js/checkout-drafts.js') }}?v=phone-only1" defer></script>
