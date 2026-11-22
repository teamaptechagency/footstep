@extends('backend.layouts.app')
@section('content')
@if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
<section class="card mb-3"><div class="card-body d-flex flex-wrap align-items-center justify-content-between" style="gap:14px;padding:15px 20px"><div><strong>Public Website Live Chat: {{ \App\Services\WebsiteLiveChat::enabled()?'ON':'OFF' }}</strong><small class="d-block text-muted">বন্ধ থাকলে product/footer chat button ও customer message API বন্ধ থাকবে। পুরোনো chat history থাকবে।</small></div>@if(auth()->user()->user_type==='admin')<form method="post" action="{{ route('website-chat.toggle') }}">@csrf<input type="hidden" name="enabled" value="{{ \App\Services\WebsiteLiveChat::enabled()?'0':'1' }}"><button class="btn {{ \App\Services\WebsiteLiveChat::enabled()?'btn-light':'btn-success' }}" type="submit">{{ \App\Services\WebsiteLiveChat::enabled()?'Turn off':'Enable Live Chat' }}</button></form>@endif</div></section>

<link rel="stylesheet" href="{{ static_asset('assets/css/website-chat-admin.css') }}?v=1">
<div class="wc-page">
 <div class="wc-channels">@include('backend.inbox.tabs')</div>
 <header class="wc-page-heading"><div><p class="wc-eyebrow">INBOX / WEBSITE CONVERSATIONS</p><h1>Website Live Chat <span>ওয়েবসাইট চ্যাট</span></h1><p>Customer conversations, together in one place.</p></div><button type="button" class="wc-refresh" data-wc-refresh><i class="las la-sync" aria-hidden="true"></i> Refresh</button></header>
 @if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
 <div class="wc-workspace {{ $thread?'wc-has-thread':'' }}">
  <aside class="wc-sidebar" aria-label="Conversations">
   <div class="wc-sidebar-heading"><h2>Conversations <span>{{ $threads->total() }}</span></h2><small>নতুন মেসেজ এখানে আসবে</small></div>
   <form class="wc-filters" method="get" action="{{ route('website-chat.index') }}">
    <label class="sr-only" for="wc-status">Conversation status</label><select id="wc-status" name="status"><option value="">All conversations</option><option value="open" @if(request('status')==='open')selected @endif>Open</option><option value="closed" @if(request('status')==='closed')selected @endif>Closed</option></select>
    <div><label class="wc-checkbox"><input type="checkbox" name="unread" value="1" @if(request('unread'))checked @endif> Unread only</label><button type="submit">Filter <i class="las la-filter" aria-hidden="true"></i></button></div>
   </form>
   <div id="website-admin-list" class="wc-list">@include('backend.website_chat.list')</div>
  </aside>
  <main class="wc-main" aria-label="Conversation messages">
  @if($thread)
   <header class="wc-conversation-heading">
    <a class="wc-back" href="{{ route('website-chat.index',request()->except(['thread','after','poll'])) }}" aria-label="Back to conversations"><i class="las la-arrow-left"></i></a>
    <span class="wc-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($person['name']??'C',0,1)) }}</span>
    <div class="wc-contact"><h2>{{ $person['name'] }}</h2><a href="tel:{{ $person['phone'] }}">{{ $person['phone'] }}</a></div>
    <form method="post" action="{{ route('website-chat.status',$thread->id) }}" class="wc-status-action">@csrf<input type="hidden" name="status" value="{{ $thread->status==='open'?'closed':'open' }}"><span class="wc-status {{ $thread->status==='open'?'is-open':'' }}">{{ ucfirst($thread->status) }}</span><button type="submit">{{ $thread->status==='open'?'Close chat':'Reopen chat' }}</button></form>
   </header>
   @if($context)
   <details class="wc-context"><summary><img src="{{ $context['image'] }}" alt=""><div><small>PRODUCT ENQUIRY</small><strong>{{ $context['name'] }}</strong><span>Size: {{ $context['selected_size']??'Not selected' }} · ৳{{ $context['price'] }}</span></div><i class="las la-angle-down" aria-hidden="true"></i></summary><div class="wc-context-details"><a href="{{ $context['url'] }}" target="_blank" rel="noopener">View product <i class="las la-external-link-alt" aria-hidden="true"></i></a><p>Snapshot at chat start · {{ ($context['available']??false)?'Available then':'Check stock' }}. Recheck current price and stock before taking an order.</p></div></details>
   @else<div class="wc-general-note"><i class="las la-comment" aria-hidden="true"></i> General enquiry · Footer contact</div>@endif
   <div id="website-admin-log" class="wc-log" role="log" aria-live="polite" aria-label="Message history">@forelse($messages as $message)<article class="wc-message {{ $message['direction'] }}" data-message-id="{{ $message['id'] }}">{{ $message['text'] }}<small>{{ $message['at'] }}</small></article>@empty<div class="wc-history-empty">No messages yet. Start the conversation below.</div>@endforelse</div>
   <form id="website-admin-reply" class="wc-composer" method="post" action="{{ route('website-chat.reply',$thread->id) }}" data-poll="{{ route('website-chat.index',['thread'=>$thread->id,'poll'=>1]) }}">@csrf<input type="hidden" name="request_id" value="{{ (string)\Illuminate\Support\Str::uuid() }}"><label class="sr-only" for="wc-reply-text">Write a reply</label><textarea id="wc-reply-text" name="text" maxlength="2000" rows="2" placeholder="Write a reply… / উত্তর লিখুন" required @if($thread->status==='closed')disabled @endif></textarea><div class="wc-composer-footer"><small>{{ $thread->status==='closed'?'Reopen this conversation to reply.':'Reply as your store · সর্বোচ্চ ২০০০ অক্ষর' }}</small><button type="submit" @if($thread->status==='closed')disabled @endif>Send reply <i class="las la-paper-plane" aria-hidden="true"></i></button></div><p id="website-admin-chat-error" role="status"></p></form>
  @else
   <div class="wc-welcome"><div class="wc-welcome-art" aria-hidden="true"><i class="las la-comments"></i><span></span></div><span class="wc-eyebrow">YOUR CUSTOMER INBOX</span><h2>A conversation starts here.</h2><p>বামের তালিকা থেকে একটি conversation বেছে নিন।<br>Customer-এর message, product ও reply এখানে দেখাবে।</p><div class="wc-welcome-features"><span><i class="las la-bolt"></i> Live updates</span><span><i class="las la-image"></i> Product context</span><span><i class="las la-volume-up"></i> Optional sound</span></div></div>
  @endif
  </main>
 </div>
</div>
<script src="{{ static_asset('assets/js/website-chat-admin.js') }}?v=ui3" defer></script>
@endsection
