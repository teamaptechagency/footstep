<div class="insight-periods" aria-label="Report period">

@foreach(['day'=>'Today','yesterday'=>'Yesterday','week'=>'Week','month'=>'Month','year'=>'Yearly'] as $id=>$label)

<a href="{{ route($reportRoute ?? 'admin.analytics',array_filter(['period'=>$id,'dashboard_layout'=>request()->query('dashboard_layout'),'source'=>($reportRoute??'admin.analytics')==='admin.analytics'?request()->query('source'):null,'product_id'=>($reportRoute??'admin.analytics')==='admin.analytics'?request()->query('product_id'):null])) }}" class="{{ $report['window']['period']===$id?'is-selected':'' }}" @if($report['window']['period']===$id) aria-current="page" @endif>{{ $label }}</a>

@endforeach

</div>


<small class="insight-period-range" style="display:block;color:#526b70;font-size:12px;line-height:1.6;margin:6px 0">{{ $report['window']['local_start']->format('d M Y') }} — {{ $report['window']['local_end']->copy()->subSecond()->format('d M Y') }} · Dhaka time</small>
