@extends('frontend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/flash-deal-groups.css') }}?v=2.4.28">
<section class="fd-groups"><div class="container">
<header class="fd-groups-head"><div><a class="fd-home" href="{{ route('home') }}">Home <span aria-hidden="true">/</span> Offers</a><h1>Flash Deals<span>Find your next pair.</span></h1><p>একটি collection বেছে নিন, তারপর সেই group-এর সব product দেখুন।</p></div><span class="fd-count">{{ $dealPaginator->total() }} active collections</span></header>
<div class="fd-groups-grid">
@forelse($dealGroups as $group)
<a class="fd-group-card" href="{{ $group['url'] }}" aria-label="{{ $group['title'] }} — {{ $group['count'] }} products">
<div class="fd-group-image"><img src="{{ $group['cover'] }}" alt="{{ $group['title'] }}" width="450" height="360" loading="{{ $loop->index<2?'eager':'lazy' }}" decoding="async" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';"><span class="fd-group-badge">COLLECTION {{ str_pad((string)($loop->index+1+($dealPaginator->currentPage()-1)*24),2,'0',STR_PAD_LEFT) }}</span></div>
<div class="fd-group-body"><div class="fd-group-title"><h2>{{ $group['title'] }}</h2><span>{{ $group['count'] }} products</span></div>
<div class="fd-group-bottom"><div class="fd-group-previews" aria-hidden="true">@foreach($group['photos'] as $photo)<img src="{{ $photo }}" alt="" width="34" height="42" loading="lazy" decoding="async" onerror="this.style.visibility='hidden'">@endforeach</div><span class="fd-group-open">View collection <span aria-hidden="true">↗</span></span></div></div>
</a>
@empty
<div class="fd-group-empty"><h2>New deals are on their way</h2><p>এখন কোনো active Flash Deal নেই। সব product দেখতে shop খুলুন।</p><a href="{{ route('search') }}">Explore products →</a></div>
@endforelse
</div>
@if($dealPaginator->hasPages())<div class="mt-4">{{ $dealPaginator->links() }}</div>@endif
</div></section>
@endsection
