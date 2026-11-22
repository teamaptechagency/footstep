@extends('frontend.layouts.app')
@section('content')
@php
 $defaults=\App\Services\OnePageCheckout::defaults(Auth::user());
 $summary=\App\Services\OnePageCheckout::base($carts);
@endphp
<section class="py-5"><div class="container checkout-one">
 <div class="mb-4"><small class="text-uppercase text-primary font-weight-bold">Footstep / Checkout</small><h1 class="h2 mt-2">আপনার অর্ডার সম্পন্ন করুন</h1><p class="text-muted">তথ্য দিন, অর্ডার করুন — পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন।</p></div>
 @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 @if(count($carts))
 <form data-checkout-draft="cart" data-draft-url="{{ route('checkout.draft') }}" id="onepage-checkout" data-meta-payment action="{{ route('checkout.onepage.store') }}" method="POST" data-member="{{ Auth::check() ? '1':'0' }}" data-old="{{ session()->hasOldInput() ? '1':'0' }}" data-base="{{ $summary['base'] }}">
 @csrf<input type="hidden" name="checkout_draft_token" value="{{ \App\Services\CheckoutDrafts::token(request()) }}"><input type="hidden" name="payment_option" value="cash_on_delivery">
 <div class="checkout-grid"><div class="checkout-surface">
 <h2 class="h5 mb-4">ডেলিভারির তথ্য</h2>
 <div class="row">
 @foreach(['name'=>['আপনার নাম','text','name'],'phone'=>['মোবাইল নম্বর','tel','tel']] as $key=>$field)
 <div class="form-group col-md-6"><label for="checkout-{{ $key }}">{{ $field[0] }}</label><input id="checkout-{{ $key }}" class="form-control" name="{{ $key }}" type="{{ $field[1] }}" autocomplete="{{ $field[2] }}" value="{{ old($key,$defaults[$key]) }}" maxlength="{{ $key==='phone' ? '25':'191' }}" required>
 @if($key==='phone')<label class="d-flex align-items-center mt-2 mb-0 small" style="gap:8px;cursor:pointer"><input id="checkout-whatsapp-same" type="checkbox" name="whatsapp_same" value="1" @if(old('whatsapp_same',$defaults['whatsapp_same'] ?? '0')==='1') checked @endif><i class="lab la-whatsapp text-success" aria-hidden="true"></i> এই নম্বরে WhatsApp আছে</label>@endif</div>
 @endforeach
 <div class="form-group col-12" id="checkout-whatsapp-group"><label for="checkout-whatsapp">WhatsApp নম্বর (আলাদা হলে, ঐচ্ছিক)</label><input id="checkout-whatsapp" name="whatsapp_phone" type="tel" class="form-control" autocomplete="off" maxlength="25" value="{{ old('whatsapp_phone',$defaults['whatsapp_phone'] ?? '') }}"></div>
 <div class="form-group col-12"><label for="checkout-address">সম্পূর্ণ ঠিকানা</label><textarea id="checkout-address" name="address" autocomplete="street-address" class="form-control" rows="3" maxlength="255" required>{{ old('address',$defaults['address']) }}</textarea></div>
 <div class="form-group col-12"><label for="checkout-city">ডেলিভারি এলাকা</label><select id="checkout-city" name="city" class="form-control" required><option value="">এলাকা নির্বাচন করুন</option>@foreach(\App\Services\OnePageCheckout::fees($carts->sum('quantity')) as $area=>$fee)<option value="{{ $area }}" data-fee="{{ $fee }}" @if(old('city',$defaults['city'])===$area) selected @endif>{{ $area }} — ৳{{ $fee }}</option>@endforeach</select></div>
 </div>
 <p class="small text-muted">@auth অর্ডার সফল হলে এই তথ্য আপনার profile-এ সেভ হবে। @else তথ্য শুধু এই browser-এ থাকবে। Site data মুছে দিলে তথ্যও মুছে যাবে। @endauth</p>
 @guest<button type="button" class="btn btn-link p-0" id="checkout-forget">Saved details মুছুন</button>@endguest
 </div><div class="checkout-surface checkout-summary"><h2 class="h5 mb-4">অর্ডারের সারাংশ</h2>
 @foreach($carts as $checkoutItem)
 @for($unit=0;$unit<(int)$checkoutItem->quantity;$unit++)
 <div class="checkout-item" data-checkout-pair="{{ $checkoutItem->id }}-{{ $unit }}"><img src="{{ \App\Services\OptimizedImages::asset($checkoutItem->product->thumbnail_img) }}" alt="" width="64" height="64" srcset="{{ \App\Services\OptimizedImages::srcset($checkoutItem->product->thumbnail_img) }}" sizes="64px" decoding="async"><div class="flex-grow-1" style="min-width:0"><a href="{{ route('product',$checkoutItem->product->slug) }}">{{ $checkoutItem->product->getTranslation('name') }}</a><strong>{{ single_price($checkoutItem->price) }}</strong>@foreach(\App\Services\ProductAddons::quote($checkoutItem->product_id,json_decode($checkoutItem->addon_ids?:'[]',true)?:[],1,false)['items'] as $cartAddon)<small class="d-block text-success">+ {{ $cartAddon['name'] }} · {{ single_price($cartAddon['price']) }}</small>@endforeach
 <div class="mt-2 d-flex align-items-center flex-wrap" style="gap:10px">
 @if($checkoutItem->product->stocks->contains(function($stock){return (string)$stock->variant!=='';}))
 <label class="small mb-0" for="size-{{ $checkoutItem->id }}-{{ $unit }}">Size</label><select id="size-{{ $checkoutItem->id }}-{{ $unit }}" class="form-control form-control-sm" style="width:auto;max-width:180px;min-height:38px" data-cart-size="{{ $checkoutItem->id }}" data-unit="{{ $unit }}">
 @foreach($checkoutItem->product->stocks as $stock)<option value="{{ $stock->id }}" @if((string)$stock->variant===(string)$checkoutItem->variation) selected @elseif($stock->qty<1) disabled @endif>{{ $stock->variant }}{{ $stock->qty<1?' — unavailable':'' }}</option>@endforeach
 </select>
 @else<span class="small text-muted">Size: Standard</span>@endif
 <button type="button" class="btn btn-link btn-sm text-danger" data-cart-remove="{{ $checkoutItem->id }}" data-unit="{{ $unit }}">Remove</button></div></div></div>
 @endfor
 @endforeach
 <div class="checkout-line"><span>Subtotal</span><span>{{ single_price($summary['subtotal']) }}</span></div>
 @if($summary['tax'])<div class="checkout-line"><span>Tax</span><span>{{ single_price($summary['tax']) }}</span></div>@endif
 @if($summary['discount'])<div class="checkout-line"><span>Discount</span><span>−{{ single_price($summary['discount']) }}</span></div>@endif
 <div class="checkout-line"><span>Delivery</span><span id="checkout-fee">এলাকা নির্বাচন করুন</span></div>
 <div class="checkout-line checkout-total"><span>Total</span><strong id="checkout-total">{{ single_price($summary['base']) }}</strong></div>
 <div class="p-3 bg-light rounded mb-3"><strong>Cash on Delivery</strong><div class="small text-muted">পণ্য হাতে পেয়ে টাকা দিন</div></div>
 <button class="btn btn-primary btn-block btn-lg" type="submit" id="checkout-submit">অর্ডার কনফার্ম করুন</button><p class="small text-danger mt-2" id="checkout-status" role="status"></p>
 </div></div></form>
 @else<div class="checkout-surface text-center py-5"><h2 class="h4">আপনার cart খালি</h2><a class="btn btn-primary mt-3" href="{{ route('home') }}">কেনাকাটা করুন</a></div>@endif
 </div></section>
 <style>.checkout-one{max-width:1180px}.checkout-grid{display:grid;grid-template-columns:1.1fr 1fr;gap:28px;align-items:start}.checkout-surface{background:#fff;padding:30px;border:1px solid #e6e9ed;border-radius:18px;box-shadow:0 8px 30px #17231b05}.checkout-one .form-control{border-radius:9px;min-height:46px}.checkout-item{display:flex;gap:14px;padding:0 0 20px;margin-bottom:20px;border-bottom:1px solid #edf0f1}.checkout-item img{object-fit:cover;border-radius:9px}.checkout-item a{color:inherit;display:block;font-weight:600}.checkout-line{display:flex;justify-content:space-between;gap:12px;margin:15px 0}.checkout-total{font-size:20px;padding-top:16px;border-top:1px solid #e6e9ed}@media(max-width:767px){.checkout-grid{grid-template-columns:1fr;gap:18px}.checkout-surface{padding:22px 18px}.checkout-one h1{font-size:26px}}</style>
@endsection
@section('script')
<script src="{{ static_asset('assets/js/checkout-drafts.js') }}?v=phone-only1" defer></script>
<script>
document.addEventListener('DOMContentLoaded',function(){
 const form=document.getElementById('onepage-checkout');if(!form)return;
 const key='footstep.checkout.guest.v1', names=['name','phone','whatsapp_phone','address','city'];
 const same=document.getElementById('checkout-whatsapp-same'), whatsapp=form.elements.whatsapp_phone;
 function syncWhatsApp(){const group=document.getElementById('checkout-whatsapp-group');group.hidden=same.checked;group.style.display=same.checked?'none':'';whatsapp.disabled=same.checked;}
 same.addEventListener('change',syncWhatsApp);
 const city=form.elements.city, status=document.getElementById('checkout-status');
 function total(){let option=city.selectedOptions[0],fee=Number(option?.dataset.fee||0);document.getElementById('checkout-fee').textContent=city.value?'৳'+fee:'এলাকা নির্বাচন করুন';document.getElementById('checkout-total').textContent='৳'+(Number(form.dataset.base)+fee).toLocaleString('en-BD',{minimumFractionDigits:2,maximumFractionDigits:2});}
 if(form.dataset.member==='0'){
  try{const saved=JSON.parse(localStorage.getItem(key)||'{}');if(form.dataset.old!=='1'){same.checked=saved.whatsapp_same==='1';names.forEach(n=>{if(!form.elements[n].value&&typeof saved[n]==='string')form.elements[n].value=saved[n];});}}catch(e){}
  function save(){try{let data={whatsapp_same:same.checked?'1':'0'};names.forEach(n=>data[n]=form.elements[n].value);localStorage.setItem(key,JSON.stringify(data));}catch(e){}}
  form.addEventListener('input',save);form.addEventListener('change',save);
  document.getElementById('checkout-forget').addEventListener('click',()=>{try{localStorage.removeItem(key);}catch(e){}names.forEach(n=>form.elements[n].value='');same.checked=false;syncWhatsApp();total();});
 }
 city.addEventListener('change',total);total();
 form.addEventListener('submit',()=>{document.getElementById('checkout-submit').disabled=true;});
 async function cartChange(url,data){
   const draft={whatsapp_same:same.checked?'1':'0'};names.forEach(n=>draft[n]=form.elements[n].value);
   try{sessionStorage.setItem('footstep.checkout.draft',JSON.stringify(draft));}catch(e){}
   document.getElementById('checkout-submit').disabled=true;
   status.textContent='Updating cart…';
   try{const body=new URLSearchParams({_token:form.elements._token.value,...data});const result=await fetch(url,{method:'POST',headers:{'X-Requested-With':'XMLHttpRequest'},body});if(!result.ok){const error=await result.json().catch(()=>({}));throw Error(error.message||'Cart update হয়নি। আবার চেষ্টা করুন।');}location.reload();}catch(e){status.textContent=e.message||'Cart update হয়নি। আবার চেষ্টা করুন।';document.getElementById('checkout-submit').disabled=false;}
 }
 try{const draft=JSON.parse(sessionStorage.getItem('footstep.checkout.draft')||'null');sessionStorage.removeItem('footstep.checkout.draft');if(draft&&form.dataset.old!=='1'){same.checked=draft.whatsapp_same==='1';names.forEach(n=>{if(typeof draft[n]==='string')form.elements[n].value=draft[n];});}total();}catch(e){}
 syncWhatsApp();
 form.querySelectorAll('[data-cart-size]').forEach(input=>input.addEventListener('change',()=>cartChange(@json(route('checkout.item')),{id:input.dataset.cartSize,unit:input.dataset.unit,action:'size',stock_id:input.value})));
 form.querySelectorAll('[data-cart-remove]').forEach(button=>button.addEventListener('click',()=>cartChange(@json(route('checkout.item')),{id:button.dataset.cartRemove,unit:button.dataset.unit,action:'remove'})));

});
</script>
@endsection
