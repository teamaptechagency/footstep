@extends('backend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/backend-menu-order.css') }}?v=1">
<div class="menu-order-workspace" id="menu-order-workspace">
    <header class="menu-order-hero"><div><span>MAKE IT YOUR WORKSPACE</span><h1>Menu Order <small>/ Menu সাজান</small></h1><p>প্রথমে যা দরকার, সেটি আগে রাখুন। Main menu ও submenu আলাদাভাবে সাজানো যাবে।</p></div><i class="las la-sort-amount-down" aria-hidden="true"></i></header>
    @if(session('menu_order_success'))<div class="alert alert-success" role="status">{{ session('menu_order_success') }}</div>@endif
    @if($errors->any())<div class="alert alert-danger" role="alert">@foreach($errors->all() as $message)<div>{{ $message }}</div>@endforeach</div>@endif
    <form method="POST" action="{{ route('admin.menu-order.save') }}" id="menu-order-form">
        @csrf
        <input type="hidden" name="revision" value="{{ $menuState['revision'] }}">
        <input type="hidden" name="menu_order" id="menu-order-value" value="{{ json_encode($menuState['order']) }}">
        <script type="application/json" id="menu-order-defaults">{!! json_encode($menuDefaults,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) !!}</script>
        <div class="menu-order-toolbar"><p><i class="las la-grip-vertical" aria-hidden="true"></i> Drag করুন অথবা <strong>↑ ↓</strong> চাপুন। প্রতিটি item নিজের group-এর মধ্যে সরবে।</p><button type="button" class="menu-order-reset" id="menu-order-reset"><i class="las la-undo" aria-hidden="true"></i> Reset to default</button></div>
        <div class="menu-order-board">
            <ul class="menu-order-list" data-order-group="root" aria-label="Main menu order">
            @foreach($menuNodes as $menuNode)
                @include('backend.platform.partials.menu_order_node',['node'=>$menuNode,'level'=>0])
            @endforeach
            </ul>
            <aside class="menu-order-help"><div><i class="las la-hand-pointer" aria-hidden="true"></i><h2>আপনার পছন্দের ক্রম</h2><p>উপরের main menu সরালে তার submenu-ও সঙ্গে যাবে। Submenu খুলে ভেতরের item আগে-পরে করতে পারবেন।</p></div><div><strong>Mobile-এ</strong><p>↑ ↓ button ব্যবহার করুন। Drag করা বাধ্যতামূলক নয়।</p></div><div><strong>Hidden menu-ও এখানে থাকবে</strong><p>Modules থেকে বন্ধ করা menu এই তালিকায় সাজানো যাবে; চালু করলে আপনার বাছাই করা জায়গায় দেখা যাবে। Staff নিজের permission অনুযায়ী menu দেখবেন।</p></div><div><strong>Data বা public site বদলাবে না</strong><p>শুধু backend sidebar-এর ক্রম বদলাবে। Save না করা পর্যন্ত নতুন ক্রম কার্যকর হবে না।</p></div></aside>
        </div>
        <div class="menu-order-savebar"><div><strong id="menu-order-changes" aria-live="polite">No unsaved changes</strong><small>এটি সব admin/staff-এর sidebar-এ প্রযোজ্য হবে।</small></div><button type="submit" id="menu-order-save"><i class="las la-check" aria-hidden="true"></i> Save menu order</button></div>
    </form>
</div>
<script src="{{ static_asset('assets/js/backend-menu-order.js') }}?v=1" defer></script>
@endsection
