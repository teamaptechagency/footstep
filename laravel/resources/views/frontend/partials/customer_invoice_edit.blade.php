@php
 $customerCanEdit=\App\Services\CustomerInvoice::editable($order);
 $customerInvoiceUrl=\Illuminate\Support\Facades\URL::temporarySignedRoute('invoice.shared',now()->addDays(7),['id'=>$order->id]);
@endphp
@if(session('customer_invoice_success'))<div role="status" style="color:#087c46;margin:12px 0">{{ session('customer_invoice_success') }}</div>@endif
@if($errors->any())<div role="alert" style="color:#a32929;margin:12px 0">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
@if($customerCanEdit)
<section class="customer-invoice-edit" style="margin:20px 0;padding:16px;border:1px solid #dbe8e0;border-radius:10px;background:#f7fbf8">
<form method="POST" action="{{ $customerInvoiceUrl }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:end">@csrf
@foreach($order->orderDetails as $customerLine)
<input type="hidden" name="lines[{{ $customerLine->id }}][quantity]" value="{{ $customerLine->quantity }}">
<label style="flex:1 1 180px;min-width:0;margin:0;font-size:13px">Size @if($order->orderDetails->count()>1){{ $loop->iteration }}@endif
<select name="lines[{{ $customerLine->id }}][stock_id]" aria-label="{{ optional($customerLine->product)->name??'Product' }} size" required style="display:block;width:100%;padding:10px;margin-top:6px;font-size:16px;background:white;border:1px solid #cfddd5;border-radius:6px">
@foreach(optional($customerLine->product)->stocks??[] as $customerStock)
@php $held=(string)$customerStock->variant===(string)$customerLine->variation?(int)$customerLine->quantity:0; @endphp
<option value="{{ $customerStock->id }}" @if((string)$customerStock->variant===(string)$customerLine->variation) selected @endif @if($customerStock->qty+$held<$customerLine->quantity) disabled @endif>{{ $customerStock->variant?:'Standard' }}{{ $customerStock->qty+$held<$customerLine->quantity?' — Unavailable':'' }}</option>
@endforeach</select></label>
@endforeach
<button type="submit" style="padding:12px 18px;min-height:45px;border:0;border-radius:7px;background:#087c46;color:white;font-weight:bold;cursor:pointer">Save size / সাইজ বদলান</button>
</form><p class="customer-size-help" style="margin:12px 0 0;color:#456452;font-size:13px;line-height:1.8"><strong>স্যার, আপনার সাইজ সঠিকভাবে বাছাই করা থাকলে</strong> এই বাটনে ক্লিক করার প্রয়োজন নেই।</p></section>
@endif
<style>@media print{.customer-invoice-edit{display:none!important}}</style>
