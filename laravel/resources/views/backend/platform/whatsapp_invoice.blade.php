@extends('backend.layouts.app')
@section('content')
<h1 class="h4">Send invoice via WhatsApp</h1>
<div class="card"><div class="card-body"><h2 class="h6">Order #{{ \App\Services\OrderDisplay::label($order) }}</h2>
@if(!$phone)<div class="alert alert-warning">The order has no valid customer phone number. Correct the customer contact information before sending.</div>@endif
<p>Recipient: <strong>{{ $phone??'Unavailable' }}</strong></p>
@if($whatsapp)
<div class="mb-3"><a id="customer-whatsapp-open" href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="btn btn-success">কাস্টমারের WhatsApp খুলুন ↗</a><small class="d-block mt-2">অর্ডারে দেওয়া নম্বরেই chat খুলবে। WhatsApp-এ Send চাপুন।</small></div>
<details class="mb-3 p-3" style="border:1px solid #dce5e1;border-radius:10px"><summary style="cursor:pointer;font-weight:600">WhatsApp নেই / খুলছে না? SMS বিকল্প</summary><p class="mt-2">নম্বরটিতে WhatsApp আছে কি না browser যাচাই করতে পারে না। WhatsApp-এ নম্বরটি না পাওয়া গেলে এই SMS বাটন ব্যবহার করুন।</p>@if($sms)<a id="customer-sms-open" href="{{ $sms }}" class="btn btn-light">এই নম্বরে SMS খুলুন</a>@endif<small class="d-block mt-2">মোবাইলের SMS app-এ একই মেসেজ ও invoice link প্রস্তুত হবে। Send চাপলে পাঠাবে; ছবি SMS-এ যাবে না।</small></details>
@endif
<label for="invoice-message">Message preview</label><textarea id="invoice-message" class="form-control mb-3" rows="7" readonly>{{ $message }}</textarea>
<p>This private invoice link expires in 7 days. Anyone with this link can view this invoice. Opening WhatsApp does not send the message; press Send there yourself.</p>
<section class="mb-3 p-3" style="background:#f1faf5;border:1px solid #cbe5d6;border-radius:12px"><h2 class="h6">ইনভয়েস + প্রথম পণ্যের ছবি + সাইজ</h2><p>ছবির সঙ্গে নিচের আগের মেসেজটিও যাবে। Share menu-তে WhatsApp ও এই কাস্টমারকে নির্বাচন করুন: <strong>{{ $phone??'Unavailable' }}</strong></p><button type="button" id="invoice-image-share" class="btn btn-success" disabled>ছবি তৈরি হচ্ছে…</button><p id="invoice-image-status" class="mt-2 mb-2" role="status" aria-live="polite"></p><div id="invoice-image-files"></div></section>
<script type="application/json" id="invoice-share-data">@json($invoiceShare)</script>
<script src="{{ static_asset('assets/js/invoice-image-share.js') }}?v=20261113" defer></script>

<a href="{{ $link }}" target="_blank" rel="noopener noreferrer" class="btn btn-light">Preview invoice</a>
<p class="text-muted mt-3">WhatsApp Web/App is required. This manual action cannot confirm delivery or read status. API credentials are not needed.</p>
</div></div>
@endsection
