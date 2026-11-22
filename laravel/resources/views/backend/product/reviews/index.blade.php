@extends('backend.layouts.app')
@section('content')
<link rel="stylesheet" href="{{ static_asset('assets/css/review-manager.css') }}?v=2.4.32">
<div class="review-manager" id="review-manager">
<header class="rv-heading"><div><span class="rv-eyebrow">CUSTOMER FEEDBACK</span><h1>Product Reviews <span>/ রিভিউ</span></h1><p>Feedback দেখুন, সংশোধন করুন এবং প্রকাশ করুন।</p></div>@if(auth()->user()->user_type==='admin')<a href="{{ route('reviews.manual.create') }}" class="btn btn-primary rv-add"><i class="las la-plus" aria-hidden="true"></i> Add review</a>@endif</header>
@if(session('manual_review_success'))<div class="alert alert-success" role="status">{{ session('manual_review_success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
<div id="rv-message" class="alert d-none" role="status" aria-live="polite"></div>
<section class="rv-tools" aria-label="Review filters"><form method="GET" action="{{ route('platform.reviews.index') }}" class="rv-filters">
<div class="rv-search"><label for="rv-search">Search / খুঁজুন</label><input id="rv-search" type="search" name="search" value="{{ request('search') }}" placeholder="Product বা review-এর কথা লিখুন"></div>
<div><label for="rv-status">Status / অবস্থা</label><select id="rv-status" name="status"><option value="">All reviews / সব</option><option value="0" {{ request('status')==='0'?'selected':'' }}>Draft / অপ্রকাশিত</option><option value="1" {{ request('status')==='1'?'selected':'' }}>Published / প্রকাশিত</option></select></div>
<div><label for="rv-rating">Sort / সাজান</label><select id="rv-rating" name="rating"><option value="">Newest first / নতুন আগে</option><option value="rating,desc" {{ request('rating')==='rating,desc'?'selected':'' }}>Rating: high to low</option><option value="rating,asc" {{ request('rating')==='rating,asc'?'selected':'' }}>Rating: low to high</option></select></div>
<div class="rv-filter-actions"><button class="btn btn-primary" type="submit">Filter</button><a class="btn btn-light" href="{{ route('platform.reviews.index') }}">Reset</a></div>
</form></section>
<div class="rv-listbar"><div><strong>{{ number_format($reviews->total()) }}</strong> results <span>· Page {{ $reviews->currentPage() }} / {{ max(1,$reviews->lastPage()) }}</span></div>
@if(auth()->user()->user_type==='admin')<form id="review-bulk-delete" method="POST" action="{{ route('reviews.manual.delete') }}" class="rv-bulk">@csrf<label class="rv-select-all"><input id="rv-select-all" type="checkbox"> Select page</label><span id="rv-selected">0 selected</span><button id="rv-bulk-button" class="btn btn-sm btn-soft-danger" disabled type="submit"><i class="las la-trash" aria-hidden="true"></i> Delete selected</button></form>@endif</div>
<div class="rv-grid">
@forelse($reviews as $review)
@if($review->product && ($review->user || $review->is_manual))
@php($sample=(json_decode($review->manualMetadata->value??'{}',true)['source']??'')==='sample')
<article class="rv-card">
<div class="rv-card-top"><div class="rv-person"><span class="rv-avatar" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($review->display_name,0,1)) }}</span><div><strong>{{ $review->display_name }}</strong><span>{{ $sample?'Sample draft / নমুনা':($review->is_manual?'Imported feedback':'Customer review') }}</span></div></div>@if($review->is_manual && auth()->user()->user_type==='admin')<input class="rv-row-select" type="checkbox" name="ids[]" value="{{ $review->id }}" form="review-bulk-delete" aria-label="Select review {{ $review->id }}">@endif</div>
<a class="rv-product" href="{{ route('product',$review->product->slug) }}" target="_blank" rel="noopener"><i class="las la-box" aria-hidden="true"></i><span>{{ $review->product->getTranslation('name') }}</span><i class="las la-external-link-alt" aria-hidden="true"></i></a>
<div class="rv-rating"><span aria-label="{{ $review->rating }} out of 5 stars">@for($star=1;$star<=5;$star++)<i class="{{ $star<=(float)$review->rating?'las':'lar' }} la-star" aria-hidden="true"></i>@endfor</span><strong>{{ $review->rating }}<small> / 5</small></strong><time>{{ $review->created_at ? $review->created_at->format('d M Y') : '' }}</time></div>
<div class="rv-comment">{{ \Illuminate\Support\Str::limit($review->comment,260) }}@if(\Illuminate\Support\Str::length($review->comment)>260)<details><summary>Read full review / পুরোটা দেখুন</summary><p>{{ $review->comment }}</p></details>@endif</div>
<footer class="rv-card-footer"><label class="rv-publish"><input type="checkbox" class="rv-publish-toggle" value="{{ $review->id }}" {{ $review->status?'checked':'' }} {{ $sample?'disabled':'' }} data-url="{{ route('reviews.published') }}"><span class="rv-status-text">{{ $review->status?'Published':'Draft' }}</span>@if($sample)<small>নমুনা · আগে edit করুন</small>@endif</label>
@if($review->is_manual && auth()->user()->user_type==='admin')<div class="rv-actions"><a class="btn btn-sm btn-light" href="{{ route('reviews.manual.edit',$review->id) }}"><i class="las la-pen" aria-hidden="true"></i> Edit</a><form method="POST" action="{{ route('reviews.manual.delete') }}" class="rv-single-delete">@csrf<input type="hidden" name="ids[]" value="{{ $review->id }}"><button class="btn btn-sm btn-soft-danger" type="submit"><i class="las la-trash" aria-hidden="true"></i> Delete</button></form></div>@endif</footer>
</article>
@endif
@empty
<div class="rv-empty"><i class="lar la-comment-dots" aria-hidden="true"></i><h2>No reviews found / রিভিউ পাওয়া যায়নি</h2><p>Filter বদলে দেখুন অথবা প্রকৃত customer feedback যোগ করুন।</p><a href="{{ route('platform.reviews.index') }}" class="btn btn-light">Reset filters</a></div>
@endforelse
</div><div class="rv-pagination aiz-pagination">{{ $reviews->appends(request()->input())->links() }}</div>
</div>
@endsection
@section('script')
<script src="{{ static_asset('assets/js/review-manager.js') }}?v=2.4.32"></script>
@endsection
