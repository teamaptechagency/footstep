@extends('backend.layouts.app')
@section('content')
<style>
.provider-wrap{max-width:1120px;margin:auto}.provider-top{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin:8px 0 22px}.provider-top h1{font-size:24px;margin:0 0 7px}.provider-grid{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,1fr);gap:20px}.provider-grid .card{margin-bottom:18px}.provider-status{padding:16px;border:1px solid #dce9e4;border-radius:12px;background:#f4faf7;margin-bottom:18px}.provider-grid label{font-weight:600}.provider-grid small{display:block;margin-top:7px;line-height:1.7;color:#617486}.provider-guide li{margin-bottom:15px;line-height:1.8}.provider-actions{display:flex;gap:10px;flex-wrap:wrap}.provider-guide a{overflow-wrap:anywhere}@media(max-width:767px){.provider-grid{grid-template-columns:1fr}.provider-top h1{font-size:21px}.provider-actions .btn{flex:1}}
</style>
<div class="provider-wrap">
 <div class="provider-top"><div><h1>{{ $card['label'] }} settings</h1><div class="text-muted">{{ $details['summary'] }}</div></div><a class="btn btn-soft-primary" href="{{ route('platform.tracking') }}">← All integrations</a></div>
 @if(session('platform_success'))<div class="alert alert-success" role="status">{{ session('platform_success') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
 <div class="provider-status"><strong>{{ $card['configured'] ? 'Enabled · ID configured / ID সংরক্ষিত' : 'Disabled / চালু নেই' }}</strong><div class="mt-1">Configuration status মাত্র; provider-এর কাছে event receipt যাচাই হয়নি।</div></div>
 <div class="provider-grid">
 <div><div class="card"><div class="card-header"><h2 class="h6 mb-0">Connection settings / প্রয়োজনীয় তথ্য</h2></div><div class="card-body">
 <form method="POST" action="{{ route('platform.tracking.provider.save', $provider) }}">@csrf
 <div class="form-group"><label><input type="hidden" name="enabled" value="0"><input type="checkbox" name="enabled" value="1" {{ old('enabled',$card['enabled'])?'checked':'' }}> Enable {{ $card['label'] }}</label></div>
 <div class="form-group"><label for="provider-id">{{ $details['id_label'] }}</label><input id="provider-id" name="id" class="form-control" value="{{ old('id',$card['id']) }}" maxlength="64" placeholder="{{ $card['placeholder'] }}" autocomplete="off" spellcheck="false" aria-describedby="id-help"><small id="id-help">সঠিক format: {{ $card['placeholder'] }}। Password/API secret এখানে দেবেন না।</small></div>
 @foreach($details['fields'] as $key=>$field)
 <div class="form-group"><label for="field-{{ $key }}">{{ $field['label'] }}</label><input id="field-{{ $key }}" name="{{ $key }}" class="form-control" value="{{ old($key,$card[$key]??'') }}" maxlength="{{ $field['max'] }}" autocomplete="off" aria-describedby="help-{{ $key }}"><small id="help-{{ $key }}">{{ $field['hint'] }}</small></div>
 @endforeach
 <div class="form-group"><label>Website</label><div class="form-control" style="height:auto;overflow-wrap:anywhere">https://footsteponline.com</div></div>
 <div class="provider-actions"><button class="btn btn-primary" type="submit">Save {{ $card['label'] }}</button><a class="btn btn-soft-primary" href="{{ $details['test'] }}" target="_blank" rel="noopener noreferrer">Open provider test ↗</a></div>
 <small class="mt-3">শুধু এই integration-এর তথ্য save হবে। অন্য provider-এর settings অপরিবর্তিত থাকবে।</small>
 </form></div></div></div>
 <div><div class="card"><div class="card-header"><h2 class="h6 mb-0">কোথা থেকে পাবেন / Setup guide</h2></div><div class="card-body provider-guide"><ol class="pl-3">@foreach($details['steps'] as $step)<li>{{ $step }}</li>@endforeach</ol><a href="{{ $details['help'] }}" target="_blank" rel="noopener noreferrer">Official setup instructions ↗</a></div></div>
 <div class="card"><div class="card-body"><h2 class="h6">কী চালু হবে / Verification</h2><p>{{ $details['scope'] }}</p><p class="mb-0">বর্তমান Next.js storefront-এ সরাসরি GA4 page-view adapter আছে। TikTok/GTM/Google Ads-এর পুরোনো browser loader Laravel public pages-এ আছে; শুধু এই settings page তৈরি হওয়া দিয়ে পুরো Next.js storefront-এ তাদের receipt নিশ্চিত হয় না। Provider test দিয়ে যে page ব্যবহার করছেন সেটি যাচাই করুন।</p></div></div></div>
 </div>
</div>
@endsection
