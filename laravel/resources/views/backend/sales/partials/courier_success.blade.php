@if(\App\Services\BackendModules::enabled('integrations.bdcourier'))
@if(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned)
<section class="cs-admin no-print" data-courier-success data-url="{{ route('courier-success.check',$order->id) }}" data-csrf="{{ csrf_token() }}"><button type="button" class="cs-trigger"><span aria-hidden="true">◴</span> {{ $courierButtonLabel??'Delivery success rate' }}</button><dialog class="cs-dialog" aria-label="Customer delivery success report"><header><div><small>ADMIN ONLY · CUSTOMER DELIVERY HISTORY</small><h2>Delivery success report</h2></div><button class="cs-close" type="button" aria-label="Close report">×</button></header><div class="cs-body" role="status"><p>Report load করতে button চাপুন।</p></div><footer><span>History সিদ্ধান্ত নেওয়ার সহায়ক; delivery guarantee নয়।</span><button class="cs-retry" type="button">Check again</button></footer></dialog></section>
@once<link rel="stylesheet" href="{{ static_asset('assets/css/courier-success.css') }}?v=2"><script src="{{ static_asset('assets/js/courier-success.js') }}?v=2" defer></script>@endonce
@endif

@endif
