@extends('backend.layouts.app')
@section('content')
<h1 class="h4 mb-3">WhatsApp API settings</h1>
@if(session('platform_success'))<div class="alert alert-success">{{ session('platform_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="alert alert-info"><strong>Setup only · Sending disabled</strong><br>এখন শুধু setup সংরক্ষণ করা যাবে। API message/invoice পাঠানো, connection test ও webhook চালু নেই। নিচের নির্দেশনা দেখে তথ্য বসান।</div>
@include('backend.platform.partials.whatsapp_setup_guide')
<form method="POST" action="{{ route('platform.whatsapp.save') }}" autocomplete="off">@csrf
<div class="card"><div class="card-body"><h2 class="h6">Business and invoice template</h2>
@php
    $waFields=[
        'phone_number_id'=>['label'=>'Phone Number ID','help'=>'Meta app → WhatsApp API Setup থেকে নিন। এটি sender-এর numeric ID; mobile number নয়।','placeholder'=>'Meta-এর Phone Number ID','numeric'=>true],
        'business_account_id'=>['label'=>'WhatsApp Business Account ID (WABA ID)','help'=>'একই app-এর WhatsApp setup বা Business Settings → WhatsApp Accounts-এ পাওয়া যায়। শুধু numeric ID দিন; email address নয়।','placeholder'=>'Numeric business account ID; email নয়','numeric'=>true],
        'sender_number'=>['label'=>'Sender phone number / Business WhatsApp নম্বর','help'=>'আপনার registered business sender-এর আসল phone number, country code-সহ। বাংলাদেশে 8801XXXXXXXXX format; customer-এর নম্বর নয়।','placeholder'=>'8801XXXXXXXXX','numeric'=>false],
        'template_name'=>['label'=>'Approved invoice template name','help'=>'WhatsApp Manager → Message templates থেকে approved template-এর exact নাম। যেমন footstep_invoice শুধুই নামের উদাহরণ; আপনার template আগে approve থাকতে হবে।','placeholder'=>'Approved template-এর নাম','numeric'=>false],
        'template_language'=>['label'=>'Template language code','help'=>'Template-এর approved language-এর exact code দিন। en_US একটি উদাহরণ; English template হলে এবং Meta-তে একই code থাকলেই ব্যবহার করুন।','placeholder'=>'যেমন en_US','numeric'=>false],
    ];
@endphp
@foreach($waFields as $key=>$field)
<label class="wa-field-label" for="wa-{{ $key }}">{{ $field['label'] }}</label>
<input id="wa-{{ $key }}" class="form-control" name="{{ $key }}" value="{{ old($key,$options[$key]??($key==='template_language'?'en_US':'')) }}" maxlength="{{ $field['numeric']?30:100 }}" placeholder="{{ $field['placeholder'] }}" aria-describedby="wa-help-{{ $key }}" autocomplete="off" @if($field['numeric']) inputmode="numeric" pattern="[0-9]{5,30}" @endif>
<p class="wa-field-help" id="wa-help-{{ $key }}">{{ $field['help'] }}</p>
@endforeach
<label for="wa-format">Preferred invoice format</label><select id="wa-format" name="invoice_format" class="form-control mb-3">@foreach(['link'=>'Private invoice link','pdf'=>'PDF document'] as $key=>$label)<option value="{{ $key }}" {{ old('invoice_format',$options['invoice_format']??'link')===$key?'selected':'' }}>{{ $label }}</option>@endforeach</select>
<p class="text-muted">Use the exact approved template name and language from WhatsApp Manager. এটি ভবিষ্যতের পছন্দ; এখনই invoice তৈরি বা API message পাঠাবে না।</p>
</div></div>
<div class="card"><div class="card-body"><h2 class="h6">Private API credentials</h2>
<p>Credentials: <strong>{{ $stored?'Encrypted credentials saved':'Not configured' }}</strong>. Blank fields preserve saved values. Values are never shown again.</p>
@foreach(['access_token'=>'Production access token','app_secret'=>'Meta App Secret','verify_token'=>'Webhook verify token (16–128 letters, numbers, underscore or hyphen)'] as $key=>$label)
<label class="wa-field-label" for="wa-{{ $key }}">{{ $label }}</label><input id="wa-{{ $key }}" type="password" class="form-control" name="{{ $key }}" value="" autocomplete="new-password" aria-describedby="wa-help-{{ $key }}">
<p class="wa-field-help" id="wa-help-{{ $key }}">@if($key==='access_token')Production-এর জন্য Meta Business Settings-এর System User থেকে প্রয়োজনীয় WhatsApp asset access ও permissions-সহ token তৈরি করুন। Test dashboard-এর temporary token expire করতে পারে। এখানে Pixel/CAPI token বসাবেন না।@elseif($key==='app_secret')এই WhatsApp business app-এর Settings → Basic → App Secret। এটি ৩২ অক্ষরের hex secret; API token নয়।@elseনিজের তৈরি random ১৬–১২৮ অক্ষরের token: letters, numbers, underscore বা hyphen। Webhook চালু হলে Meta-তে একই মান বসাতে হবে; আপাতত ফাঁকা রাখতে পারেন।@endif <strong>ফাঁকা রাখলে আগের saved value থাকবে।</strong></p>
@endforeach
<label class="d-block"><input type="checkbox" name="remove_credentials" value="1"> Remove all saved API credentials</label>
<label for="wa-password">Current admin password (required only for credential changes)</label><input id="wa-password" type="password" class="form-control mb-3" name="password" autocomplete="current-password" value="">
<p class="text-muted">Credentials are encrypted with the application key. Webhook URL will be supplied when the receiving endpoint is implemented; do not configure a guessed URL in Meta.</p>
</div></div>
<button class="btn btn-primary" type="submit">Save WhatsApp setup</button>
</form>
<div class="card mt-3"><div class="card-body"><h2 class="h6">Before enabling automatic messages later</h2><ol><li>Register your business sender and configure billing in Meta.</li><li>Approve the invoice notification template.</li><li>Connect invoice links/PDF, customer opt-in, background worker and verified webhooks.</li><li>Test delivery with your own number before enabling customer messages.</li></ol></div></div>
@endsection
