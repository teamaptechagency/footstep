@php($analyticsPayload=\App\Services\AnalyticsTracker::payload(request(),$detailedProduct??null))
@if($analyticsPayload)
<div id="storefront-analytics" hidden data-payload="{{ $analyticsPayload }}" data-endpoint="{{ route('analytics.collect') }}" data-csrf="{{ csrf_token() }}"></div>
<script src="{{ static_asset('assets/js/storefront-analytics.js') }}?v=2.1.2" defer></script>
@endif
