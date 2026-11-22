@extends('backend.layouts.app')
@section('content')
<div class="alert alert-info">Unified messaging is now available in <a href="{{ route('inbox.settings') }}">AI Inbox connections</a>. These older reserved credentials are not copied or activated automatically.</div>
<div class="d-flex align-items-center justify-content-between mb-3"><h1 class="h4 mb-0">Messenger API setup / মেসেঞ্জার সেটআপ</h1><a class="btn btn-soft-primary" href="{{ route('platform.tracking') }}">Marketing integrations</a></div>
@if(session('platform_success'))<div class="alert alert-success">{{ session('platform_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
<div class="alert alert-info"><strong>Draft / Disabled — পরে চালু করার জন্য সংরক্ষিত</strong><p class="mb-0 mt-2">Save the Page and app details here for a future Messenger connection. Saving does not activate a webhook or send messages. / ভবিষ্যতে API সংযোগের জন্য তথ্য এখানে সেভ করুন। এখন webhook ও message sending বন্ধ থাকবে।</p></div>
<form action="{{ route('platform.messenger.save') }}" method="POST">@csrf
<div class="card"><div class="card-header"><h2 class="h6 mb-0">Facebook Page & Meta app</h2></div><div class="card-body">
@foreach(['page_id'=>'Facebook Page ID','page_username'=>'Page username','app_id'=>'Meta App ID'] as $field=>$label)<div class="form-group"><label for="messenger-{{ $field }}">{{ $label }}</label><input class="form-control" id="messenger-{{ $field }}" name="{{ $field }}" value="{{ old($field,$options[$field]??'') }}" maxlength="100" autocomplete="off"></div>@endforeach
<div class="form-group mb-0"><label>Reserved webhook callback URL / ভবিষ্যতের webhook URL</label><input class="form-control" readonly value="{{ url('/integrations/messenger/webhook') }}"><small class="text-muted">Not active yet. Activate the webhook implementation before adding this URL in Meta. / এখনো সক্রিয় নয়; webhook চালুর পরে Meta-তে এই URL দিন।</small></div>
</div></div>
<div class="card"><div class="card-header"><h2 class="h6 mb-0">Credentials / API তথ্য</h2><span class="text-muted">{{ $stored?'Encrypted credentials saved / এনক্রিপ্টেড তথ্য সেভ আছে':'Not configured / তথ্য দেওয়া হয়নি' }}</span></div><div class="card-body">
@foreach(['access_token'=>'Page access token','app_secret'=>'Meta App Secret','verify_token'=>'Webhook verify token'] as $field=>$label)<div class="form-group"><label for="messenger-{{ $field }}">{{ $label }}</label><input class="form-control" id="messenger-{{ $field }}" name="{{ $field }}" type="password" autocomplete="new-password" maxlength="4096" placeholder="Leave blank to keep the saved value / আগের তথ্য রাখতে ফাঁকা রাখুন"></div>@endforeach
<div class="form-group"><label for="messenger-password">Current admin password / বর্তমান অ্যাডমিন পাসওয়ার্ড</label><input class="form-control" id="messenger-password" name="password" type="password" autocomplete="current-password"><small class="text-muted">Required only when saving or removing credentials. / API তথ্য পরিবর্তন বা মুছতে প্রয়োজন।</small></div>
<label><input type="checkbox" name="remove_credentials" value="1"> Remove saved credentials / সেভ করা API তথ্য মুছুন</label>
</div></div>
<div class="card"><div class="card-header"><h2 class="h6 mb-0">Future product message / ভবিষ্যতে যে তথ্য পাঠাবে</h2></div><div class="card-body">
@foreach(['include_name'=>'Product name / পণ্যের নাম','include_size'=>'Selected size / নির্বাচিত সাইজ','include_price'=>'Price / দাম','include_link'=>'Product link / পণ্যের লিংক'] as $field=>$label)<label class="d-block mb-3"><input type="checkbox" name="{{ $field }}" value="1" @if(old($field,$options[$field]??true)) checked @endif> {{ $label }}</label>@endforeach
<p class="text-muted mb-0">Later setup requires the Page connection, pages_messaging permission and a verified, signed webhook handler. / পরে Page সংযোগ, pages_messaging permission ও যাচাইকৃত webhook handler চালু করতে হবে।</p>
</div></div><button class="btn btn-primary" type="submit">Save setup / সেটিং সেভ করুন</button></form>
@endsection
