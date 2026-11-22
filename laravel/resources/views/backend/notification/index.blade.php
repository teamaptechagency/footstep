@extends('backend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/admin-delivery-activity.css') }}?v=20261114">
<section class="delivery-activity">
    <div class="delivery-heading"><div><span class="delivery-eyebrow">ORDER UPDATES · বাংলাদেশ সময়</span><h2>নোটিফিকেশন</h2><p>নতুন অর্ডার, কনফার্ম ও ডেলিভারি আলাদাভাবে দেখুন। একটি খুললে শুধু সেটিই read হবে।</p></div><a class="delivery-link" href="{{ request()->fullUrl() }}">↻ রিফ্রেশ</a></div>
    @if(session('info'))<p class="alert alert-info">{{ session('info') }}</p>@endif
    <nav class="order-notification-tabs" aria-label="Notification categories">
        <a class="{{ !request('category')?'active':'' }}" href="{{ route('admin.all-notification',request()->only('unread')) }}">সব <span>({{ array_sum($counts) }})</span></a>
        @foreach(\App\Services\OrderNotificationFeed::GROUPS as $key=>$group)
        <a class="{{ request('category')===$key?'active':'' }}" href="{{ route('admin.all-notification',array_merge(request()->only('unread'),['category'=>$key])) }}">{{ $group['label'] }} ({{ $counts[$key] }})</a>
        @endforeach
    </nav>
    <form method="get" class="delivery-filter"><input type="hidden" name="category" value="{{ request('category') }}"><label><span><input type="checkbox" name="unread" value="1" @if(request('unread')) checked @endif> শুধু unread</span></label><button type="submit">দেখুন</button><span>Tab-এর সংখ্যা শুধু unread নোটিফিকেশন বোঝায়।</span></form>
    <div class="order-notification-list">
    @forelse($notifications as $notification)
        @php($item=\App\Services\OrderNotificationFeed::item($notification))
        <a class="{{ $notification->read_at?'':'unread' }}" href="{{ $item['url'] }}"><span class="order-notification-badge">{{ $item['label'] }}</span>{{ $item['code'] }}<small>{{ ucwords(str_replace('_',' ',$item['status'])) }} · {{ $item['at'] }} · BD @if(!$notification->read_at) · Unread @endif</small></a>
    @empty
        <p class="py-4 text-muted">এই বিভাগে কোনো নোটিফিকেশন নেই।</p>
    @endforelse
    </div>
    <div class="mt-3">{{ $notifications->links() }}</div>
</section>
@endsection
