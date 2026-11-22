@if(\App\Services\BackendModules::enabled('integrations.steadfast'))
@if(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned)
@php
 $courierParcel=\App\Services\StoreOperations::shipment($order->id);
 $courierLocked=$courierParcel&&!($courierParcel['mock']??false)&&in_array($courierParcel['state']??'',['booked','sending','uncertain'],true);
 $courierOptions=\App\Services\SteadfastAccounts::listing();
@endphp
<div class="steadfast-account-buttons no-print" style="display:flex;gap:6px;flex-wrap:wrap;margin:8px 0">
@if($courierLocked)<strong class="small">Steadfast · {{ $courierParcel['account_label']??'Main account' }} · {{ $courierParcel['status']??$courierParcel['state'] }}</strong>
@else
@foreach($courierOptions as $courierAccount)
@if($courierAccount['active'])<button type="button" class="btn btn-sm btn-outline-success invoice-button" data-courier-send="{{ route('steadfast-accounts.send',['account'=>$courierAccount['id'],'order'=>$order->id]) }}" data-courier-label="{{ $courierAccount['label'] }}" data-courier-mode="{{ $courierAccount['mode'] }}" data-courier-order="{{ $order->id }}" data-token="{{ csrf_token() }}" @if(in_array($order->delivery_status,['delivered','cancelled'],true))disabled @endif>Send · {{ $courierAccount['label'] }}{{ $courierAccount['mode']==='mock'?' (Mock)':'' }}</button>@endif
@endforeach
@endif
<span class="small" role="status" data-courier-result="{{ $order->id }}"></span>
</div>
@once<script src="{{ static_asset('assets/js/steadfast-accounts.js') }}?v=2.4.26" defer></script>@endonce
@endif

@endif

@if(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned)
@include('backend.sales.partials.carrybee_button')
@endif
