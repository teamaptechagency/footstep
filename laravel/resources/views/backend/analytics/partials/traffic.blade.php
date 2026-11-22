<div class="insight-panel insight-traffic">
<div class="insight-panel-heading"><div><span class="insight-eyebrow">STORE ACTIVITY</span><h2>Visitor overview <small>/ ভিজিটর</small></h2></div><span class="insight-chip">{{ $report['window']['label'] }} · Dhaka time</span></div>
<div class="insight-traffic-totals"><div><strong>{{ number_format($report['traffic']['visitors']) }}</strong><span><i class="insight-dot"></i> Unique browsers</span></div><div><strong>{{ number_format($report['traffic']['views']) }}</strong><span><i class="insight-dot alternate"></i> Page views</span></div><div><strong>{{ \App\Services\StoreAnalytics::duration($report['traffic']['average']) }}</strong><span>Avg. active time / view</span></div></div>
@if($report['traffic']['views'])
@php
 $points=[];$visitorPoints=[];$max=max(1,max(array_column($report['series'],'views')));$count=count($report['series']);
 foreach($report['series'] as $i=>$point){$x=20+($i/max(1,$count-1))*760;$points[]=round($x,2).','.round(190-($point['views']/$max)*160,2);$visitorPoints[]=round($x,2).','.round(190-($point['visitors']/$max)*160,2);}
@endphp
<div class="insight-chart"><svg viewBox="0 0 800 220" role="img" aria-label="Page views and unique browsers by period"><defs><linearGradient id="insight-area" x1="0" x2="0" y1="0" y2="1"><stop offset="0%" stop-color="#13a688" stop-opacity=".22"/><stop offset="100%" stop-color="#13a688" stop-opacity="0"/></linearGradient></defs>
@foreach([30,70,110,150,190] as $line)<line x1="20" x2="780" y1="{{ $line }}" y2="{{ $line }}" stroke="#edf0f4"/>@endforeach
<polygon points="20,190 {{ implode(' ',$points) }} 780,190" fill="url(#insight-area)"/><polyline points="{{ implode(' ',$points) }}" fill="none" stroke="#12a288" stroke-width="3" stroke-linejoin="round"/><polyline points="{{ implode(' ',$visitorPoints) }}" fill="none" stroke="#697bdf" stroke-width="2" stroke-dasharray="5 4"/>
@foreach($report['series'] as $i=>$point)<circle cx="{{ 20+($i/max(1,$count-1))*760 }}" cy="{{ 190-($point['views']/$max)*160 }}" r="5" fill="#12a288"><title>{{ $point['label'] }} · {{ $point['views'] }} views · {{ $point['visitors'] }} browsers</title></circle>@endforeach
</svg></div><div class="insight-chart-labels"><span>{{ $report['series'][0]['label'] }}</span><span>{{ $report['series'][intdiv(count($report['series'])-1,2)]['label'] }}</span><span>{{ end($report['series'])['label'] }}</span></div>
@else<div class="insight-empty"><i class="las la-chart-area" aria-hidden="true"></i><strong>No visitor data for this period</strong><span>নতুন customer visit থেকে এখানে তথ্য জমবে। Admin/staff visits are excluded.</span></div>@endif
</div>
