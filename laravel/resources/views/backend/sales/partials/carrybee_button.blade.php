@php
    $carrybeeParcel=\App\Services\StoreOperations::shipment($order->id);
    $carrybeeSettings=\App\Services\CarryBee::settings();
    $carrybeeAmount=$order->payment_status==='paid'?0:(int)ceil((float)$order->grand_total);
    $carrybeeLocked=in_array($carrybeeParcel['state']??'',['sending','uncertain','booked'],true);
    $carrybeeReady=$carrybeeSettings['enabled']&&!empty($carrybeeSettings['store_id'])&&!empty($carrybeeSettings['client_id'])&&!empty($carrybeeSettings['client_secret'])&&!empty($carrybeeSettings['client_context']);
@endphp
@if(($carrybeeParcel['provider']??'')==='carrybee')
<a class="btn btn-sm btn-outline-primary no-print" href="{{ route('carrybee.order',$order->id) }}">CarryBee · Tracking / Reconcile</a>
@elseif($carrybeeLocked)
<button class="btn btn-sm btn-outline-secondary no-print" disabled>CarryBee · Courier already assigned</button>
@elseif(!$carrybeeReady)
<a class="btn btn-sm btn-outline-primary no-print" href="{{ route('carrybee.index') }}">Configure CarryBee</a>
@else
<form method="POST" class="d-inline no-print" action="{{ route('carrybee.action',$order->id) }}" onsubmit="return confirm('Send this parcel to CarryBee?')">
@csrf
<input type="hidden" name="action" value="book">
<input type="hidden" name="delivery_type" value="1">
<button type="submit" class="btn btn-sm btn-outline-success" @if(in_array($order->delivery_status,['cancelled','delivered'],true)) disabled @endif>Send · CarryBee</button>
@if($order->payment_status!=='paid' && (float)$order->grand_total!==floor((float)$order->grand_total))
<small class="d-block text-muted">CarryBee COD ৳{{ number_format($carrybeeAmount,0) }}</small>
@endif
</form>
@endif
