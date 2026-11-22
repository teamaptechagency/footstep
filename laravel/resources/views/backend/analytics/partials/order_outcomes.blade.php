@if(\App\Services\BackendModules::enabled('analytics.outcomes'))
@php
    $outcomeWindow=isset($report)?$report['window']:\App\Services\StoreAnalytics::window(request('period','day'));
    $outcome=isset($report)?\App\Services\OrderOutcomeReport::fromStatuses($report['statuses']):\App\Services\OrderOutcomeReport::report($outcomeWindow['period'])['outcome'];
    $outcomeRoute=request()->route() && request()->route()->getName()==='admin.analytics'?'admin.analytics':'admin.dashboard';
@endphp
<link rel="stylesheet" href="{{ static_asset('assets/css/order-outcomes.css') }}?v=2.6.0">
<section class="outcome-panel" aria-label="Order success versus cancellation report">
    <header class="outcome-heading"><div><span>ORDER OUTCOMES</span><h2>Success vs Cancelled <small>/ অর্ডারের ফলাফল</small></h2></div><div class="outcome-periods" role="group" aria-label="Reporting period">
    @foreach(['day'=>'Today','yesterday'=>'Yesterday','week'=>'Weekly','month'=>'Monthly','year'=>'Yearly'] as $period=>$label)
        <a class="{{ $outcomeWindow['period']===$period?'active':'' }}" href="{{ route($outcomeRoute,array_merge(request()->except(['period','page']),['period'=>$period])) }}" @if($outcomeWindow['period']===$period) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
    </div></header>
    <div class="outcome-content">
        <div class="outcome-pie-wrap"><div class="outcome-pie {{ $outcome['finalized']?'':'empty' }}" style="--success:{{ number_format($outcome['success_rate']??0,2,'.','') }}%" role="img" aria-label="{{ $outcome['delivered'] }} delivered, {{ $outcome['cancelled'] }} cancelled; {{ $outcome['other'] }} other orders excluded from the success rate">
            <div><strong>{{ $outcome['success_rate']===null?'—':number_format($outcome['success_rate'],1).'%' }}</strong><span>Delivery success</span></div>
        </div><p>{{ number_format($outcome['finalized']) }} finalized orders</p></div>
        <div class="outcome-counts">
            <div class="outcome-metric"><span><i class="success"></i>Delivered / সফল</span><strong>{{ number_format($outcome['delivered']) }}</strong><small>{{ $outcome['success_rate']===null?'No final result':number_format($outcome['success_rate'],1).'% of finalized' }}</small></div>
            <div class="outcome-metric"><span><i class="cancel"></i>Cancelled / বাতিল</span><strong>{{ number_format($outcome['cancelled']) }}</strong><small>{{ $outcome['cancel_rate']===null?'No final result':number_format($outcome['cancel_rate'],1).'% of finalized' }}</small></div>
            <div class="outcome-metric"><span><i class="other"></i>Pending / অন্যান্য</span><strong>{{ number_format($outcome['other']) }}</strong><small>Pie ও success rate-এর বাইরে</small></div>
            <div class="outcome-metric"><span>Total placed / মোট অর্ডার</span><strong>{{ number_format($outcome['total']) }}</strong><small>{{ $outcomeWindow['label'] }} · Asia/Dhaka</small></div>
        </div>
    </div>
    <footer>নির্বাচিত সময়ে তৈরি order-এর বর্তমান status। Success = Delivered ÷ (Delivered + Cancelled)। Pending/অন্যান্য বাদ। Weekly = চলতি সপ্তাহ; Monthly/Yearly = চলতি মাস/বছর। Report cache সর্বোচ্চ ৬০ সেকেন্ড।</footer>
</section>

@endif
