@php
 $courierLabel=\App\Services\CourierLabel::data($order);
 $booked=($shipment['state']??'')==='booked'&&!empty($shipment['consignment_id']);
@endphp
<section class="card no-print" data-module-feature="integrations.steadfast"><div class="card-body">
<div class="d-flex justify-content-between flex-wrap mb-3" style="gap:10px"><h2 class="h5 mb-0">{{ ($shipment['provider']??'')==='carrybee'?'CarryBee':'Steadfast' }} Invoice / Courier details</h2>@if($booked)<a class="btn btn-primary" href="{{ route('store-ops.label',$order->id) }}" target="_blank" rel="noopener">Print courier label ↗</a>@endif</div>
@if($shipment['mock']??false)<span class="badge badge-warning">MOCK — no real parcel</span>@endif
<div class="row">@foreach(['Account'=>$shipment['account_label']??'—','Consignment ID'=>$shipment['consignment_id']??'Not booked','Steadfast Invoice'=>$shipment['invoice']??'—','Tracking code'=>$shipment['tracking_code']??'—','Courier status'=>\App\Services\CourierOrderStatus::courierLabel($shipment),'Customer mobile'=>$courierLabel['recipient_phone']] as $name=>$value)<div class="col-sm-6 col-lg-4 mb-3"><small class="d-block text-muted">{{ $name }}</small><strong style="overflow-wrap:anywhere">{{ $value }}</strong></div>@endforeach</div>
<p><b>Recipient:</b> {{ $courierLabel['recipient_name'] }} · {{ $courierLabel['recipient_address'] }}</p><p><b>COD:</b> ৳{{ number_format($courierLabel['cod_amount'],2) }}</p>
<div class="d-flex flex-wrap mt-2" style="gap:8px">@include('backend.sales.partials.steadfast_account_buttons')<form method="POST" action="{{ route('store-ops.sync',$order->id) }}">@csrf<button class="btn btn-light" @unless($booked) disabled @endunless>Sync delivery status</button></form><a class="btn btn-light" href="https://portal.packzy.com" target="_blank" rel="noopener">Courier portal ↗</a></div>
<small class="d-block mt-3 text-muted">Photo ও size-সহ merchant label website তৈরি করে। Steadfast-এর official routing label/barcode portal থেকে নিন। নতুন booking-এ product/size text পাঠানো হবে; পুরোনো parcel পরিবর্তন হবে না।</small>
</div></section>
