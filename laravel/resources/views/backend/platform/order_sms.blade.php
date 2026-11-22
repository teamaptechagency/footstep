@extends('backend.layouts.app')
@section('content')
<style>
.sms-panel{max-width:1200px;margin:auto}.sms-head{display:flex;gap:15px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin-bottom:20px}.sms-head h1{font-size:25px;margin:0}.sms-sub{color:#718080;font-size:13px;margin:8px 0}.sms-summary{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin:20px 0}.sms-summary>div{background:#fff;border:1px solid #e0e9e7;border-radius:12px;padding:16px}.sms-summary strong{display:block;font-size:22px;color:#175044}.sms-grid{display:grid;grid-template-columns:1.2fr 1fr;gap:20px}.sms-card{background:white;border:1px solid #e1e9e7;border-radius:14px;padding:22px;margin-bottom:20px}.sms-card h2{font-size:18px;margin:0 0 18px}.sms-card label{display:block;margin:15px 0 6px;font-size:13px;font-weight:600}.sms-card .form-control{width:100%;height:auto;min-height:44px}.sms-card small{display:block;color:#73847d;line-height:1.7;margin-top:6px}.sms-card .sms-check{display:flex;gap:8px;align-items:center}.sms-preview{white-space:pre-wrap;background:#f1f8f5;border:1px solid #d7e9df;padding:18px;border-radius:10px;line-height:1.8;overflow-wrap:anywhere;min-height:150px}.sms-token{font-size:12px;margin:4px;padding:5px 8px;border:1px solid #d4e4df;border-radius:5px;background:#f3f8f6}.sms-table{overflow-x:auto}.sms-table table{min-width:700px;width:100%;font-size:12px}.sms-table td,.sms-table th{padding:12px;border-bottom:1px solid #e4ebe8;vertical-align:top}.sms-notice{padding:13px;border-radius:8px;background:#fff7e8;font-size:13px;line-height:1.8}.sms-worker{font-size:12px;color:#64756e;overflow-wrap:anywhere}@media(max-width:767px){.sms-grid{grid-template-columns:1fr}.sms-summary{grid-template-columns:1fr 1fr}.sms-card{padding:16px}.sms-head h1{font-size:21px}.sms-card input,.sms-card select,.sms-card textarea{font-size:16px}}
</style>
<div class="sms-panel">
 <div class="sms-head"><div><h1>Order SMS · Provider settings</h1><p class="sms-sub">Integrations / অর্ডারের স্বয়ংক্রিয় SMS ও custom message</p></div><a href="{{ $settings['provider']==='onecodesoft'?'https://onecodesoft.com/user-panel/bulk-sms/sms-price':'https://portal.sms.net.bd/sms_rate' }}" target="_blank" rel="noopener noreferrer" class="btn btn-light">SMS rates ↗</a></div>
 @if(session('sms_success'))<div class="alert alert-success">{{ session('sms_success') }}</div>@endif
 @if(session('sms_error'))<div class="alert alert-warning">{{ session('sms_error') }}</div>@endif
 @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
 @unless($liveAllowed)<div class="sms-notice">এই সার্ভারে লাইভ SMS বন্ধ আছে। Mock বা Sandbox mode-এ সেটআপ পরীক্ষা করুন।</div>@endunless
 <div class="sms-summary"><div><strong>{{ $counts['pending'] ?? 0 }}</strong>Waiting / অপেক্ষায়</div><div><strong>{{ $counts['accepted'] ?? 0 }}</strong>Provider accepted / গৃহীত</div><div><strong>{{ ($counts['failed'] ?? 0)+($counts['uncertain'] ?? 0) }}</strong>Needs attention / পরীক্ষা করুন</div><div><strong>{{ $counts['mocked'] ?? 0 }}</strong>Mocked / পাঠানো হয়নি</div></div>
 <div class="sms-grid"><form method="POST" action="{{ route('platform.sms.save') }}" class="sms-card" autocomplete="off">@csrf
  <h2>Connection & automation</h2>
<label for="sms-provider">SMS provider</label><select id="sms-provider" name="provider" class="form-control"><option value="smsnet" @if(old('provider',$settings['provider'])==='smsnet') selected @endif>SMS.net.bd</option><option value="onecodesoft" @if(old('provider',$settings['provider'])==='onecodesoft') selected @endif>OneCodeSoft API v3</option></select><small>প্রতিটি provider-এর key আলাদা encrypted অবস্থায় থাকে। Provider বদলালে তার নিজের key ব্যবহার হবে।</small>
<label class="sms-check"><input type="checkbox" id="sms-masking" name="masking" value="1" @if(old('masking',session()->hasOldInput()?false:$settings['masking'])) checked @endif> Masking / branded sender required</label><small>OneCodeSoft-এ approved brand Sender ID না থাকলে Masking SMS যাবে না। Sandbox-এ validation হয়; real SMS বা charge হয় না।</small>
  <label class="sms-check"><input type="checkbox" name="enabled" value="1" @if(old('enabled',session()->hasOldInput()?false:$settings['enabled'])) checked @endif> Enable order SMS / চালু করুন</label>
  <label for="sms-mode">Mode</label><select class="form-control" id="sms-mode" name="mode"><option value="mock" @if(old('mode',$settings['mode'])==='mock') selected @endif>Mock — কোনো real SMS নয়</option><option value="sandbox" @if(old('mode',$settings['mode'])==='sandbox') selected @endif>OneCodeSoft Sandbox — no SMS / no charge</option><option value="live" @if(old('mode',$settings['mode'])==='live') selected @endif>Live — customer-কে real SMS</option></select>
  <label for="sms-trigger">SMS কখন যাবে?</label><select class="form-control" id="sms-trigger" name="trigger"><option value="confirmed" @if(old('trigger',$settings['trigger'])==='confirmed') selected @endif>Admin Confirm করলে</option><option value="placed" @if(old('trigger',$settings['trigger'])==='placed') selected @endif>Customer order জমা দিলে</option></select><small>চালু করার পর নতুন event-এর জন্য কাজ করবে। পুরোনো order-এ একসঙ্গে SMS যাবে না। একই order/event-এ একবার।</small>
  <label for="sms-key">API key · {{ $keySaved?'Saved / সংরক্ষিত':'Not configured / দেওয়া হয়নি' }}</label><input class="form-control" type="password" id="sms-key" name="api_key" autocomplete="new-password" placeholder="Blank keeps saved key" maxlength="4096"><small>SMS portal → API থেকে key নিন। Encrypted থাকে; browser-এ ফেরত দেখানো হয় না।</small>
  @php
    $usesDefaultSender = old('provider', $settings['provider']) === 'onecodesoft'
        && !old('masking', session()->hasOldInput() ? false : $settings['masking']);
  @endphp
  <div id="sms-sender-field" @if($usesDefaultSender) hidden @endif>
    <label for="sms-sender">Sender ID / অনুমোদিত প্রেরকের নাম</label>
    <input class="form-control" id="sms-sender" name="sender_id" value="{{ old('sender_id', $settings['sender_id']) }}" maxlength="80" autocomplete="off" @if($usesDefaultSender) disabled @endif>
    <small>OneCodeSoft Masking SMS-এর জন্য অনুমোদিত brand Sender ID দিন।</small>
  </div>
  <small id="sms-default-sender" @unless($usesDefaultSender) hidden @endunless>Non-Masking SMS-এ OneCodeSoft-এর default sender ব্যবহার হবে।</small>
  <label for="sms-message">Custom message / আপনার SMS</label><textarea class="form-control" id="sms-message" name="message" rows="6" maxlength="1200" required>{{ old('message',$settings['message']) }}</textarea>
  <div>@foreach(\App\Services\OrderSms::TOKENS as $token)<button class="sms-token" type="button" data-token="{{ '{'.$token.'}' }}">{{ '{'.$token.'}' }}</button>@endforeach</div><small>নমুনা text নিজের মতো বদলান। {invoice_url} ব্যক্তিগত signed link (৭ দিন)। বাংলা/লম্বা text-এ একাধিক SMS charge হতে পারে; portal-এর rate প্রযোজ্য।</small>
  <label for="sms-password">Current admin password</label><input class="form-control" type="password" id="sms-password" name="password" autocomplete="current-password"><small>Key বা live settings পরিবর্তনে প্রয়োজন।</small>
  <label class="sms-check"><input type="checkbox" name="remove_key" value="1"> Remove saved API key</label>
  <button class="btn btn-primary mt-3" type="submit">Save SMS settings</button>
 </form><div><section class="sms-card"><h2>Message preview / নমুনা</h2><div class="sms-preview" id="sms-preview"></div><small class="sms-sub">এটি sample data; কোনো customer-কে পাঠানো হচ্ছে না।</small></section>
 <section class="sms-card"><h2>Connection test</h2><p>Saved API key দিয়ে শুধু balance যাচাই হবে। SMS পাঠানো হবে না।</p><form method="POST" action="{{ route('platform.sms.test') }}">@csrf<button class="btn btn-light" type="submit">Test connection / Check balance</button></form><p class="sms-worker mt-3">Worker: {{ $worker ?: 'এখনো চালুর প্রমাণ নেই' }}</p><small>VPS scheduler প্রতি মিনিটে outbox চালায়। Shared hosting-এ ৫ মিনিট interval হলে SMS-এ ততটা অপেক্ষা হতে পারে।</small></section>
 <section class="sms-card"><h2>Status কী বোঝায়?</h2><p><b>Accepted:</b> Provider request নিয়েছে; delivery নিশ্চিত নয়।<br><b>Failed:</b> key/number/balance/provider rejection পরীক্ষা করুন।<br><b>Uncertain:</b> request পৌঁছেছে কি না নিশ্চিত নয়—portal দেখে নিন; automatic resend হয় না।<br><b>Mocked:</b> Test flow হয়েছে, real SMS যায়নি।</p><a href="{{ $settings['provider']==='onecodesoft'?'https://onecodesoft.com/docs/bulks-sms-v3':'https://sms.bd/api' }}" target="_blank" rel="noopener noreferrer">Official API documentation ↗</a></section></div></div>
 <section class="sms-card"><h2>Recent order SMS / সর্বশেষ অবস্থা</h2><div class="sms-table"><table><thead><tr><th>Order</th><th>Event / Mode</th><th>Mobile</th><th>Status</th><th>Provider reference</th><th>Time</th></tr></thead><tbody>@forelse($logs as $log)<tr><td>{{ $log->code ?: '#'.$log->order_id }}</td><td>{{ $log->event }} · {{ $log->mode }}</td><td>{{ $log->recipient_hint ?: '—' }}</td><td><b>{{ $log->state }}</b><br>{{ $log->notice }}</td><td>{{ $log->provider_id ?: '—' }}</td><td>{{ $log->created_at }}</td></tr>@empty<tr><td colspan="6">এখনো কোনো SMS event নেই। Settings চালু করার পর নতুন order/confirmation এখানে আসবে।</td></tr>@endforelse</tbody></table></div>{{ $logs->links() }}</section>
</div>
@endsection
@section('script')
<script>
(function () {
    const provider = document.getElementById('sms-provider');
    const masking = document.getElementById('sms-masking');
    const field = document.getElementById('sms-sender-field');
    const sender = document.getElementById('sms-sender');
    const hint = document.getElementById('sms-default-sender');

    function updateSenderField() {
        const usesDefaultSender = provider.value === 'onecodesoft' && !masking.checked;
        field.hidden = usesDefaultSender;
        sender.disabled = usesDefaultSender;
        hint.hidden = !usesDefaultSender;
    }

    provider.addEventListener('change', updateSenderField);
    masking.addEventListener('change', updateSenderField);
    updateSenderField();
})();
(()=>{const input=document.getElementById('sms-message'),preview=document.getElementById('sms-preview');const samples={'{customer_name}':'Sample Customer','{order_number}':'20261110-12345678','{total}':'5999.50','{invoice_url}':'https://your-domain.example/shared-invoice/sample'};function update(){preview.textContent=input.value.replace(/\{[^{}]+\}/g,key=>samples[key]||key);}input.addEventListener('input',update);document.querySelectorAll('[data-token]').forEach(button=>button.addEventListener('click',()=>{input.setRangeText(button.dataset.token,input.selectionStart,input.selectionEnd,'end');input.focus();update();}));update();})();
</script>
@endsection
