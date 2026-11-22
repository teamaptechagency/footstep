@php
 \App\Services\CourierOrderStatus::prime($orders);
@endphp
<table class="table order-list-table mb-0">
<thead><tr><th class="ol-select">@if(!$inhouseList)<label class="aiz-checkbox"><input type="checkbox" class="check-all" aria-label="Select all orders"><span class="aiz-square-check"></span></label>@else # @endif</th><th>Order / Customer</th><th>Products / Size</th><th>Amount</th><th>Delivery</th><th>Payment</th><th class="text-right">Actions</th></tr></thead>
<tbody>@forelse($orders as $key=>$order)
@php
 $olAddress=json_decode($order->shipping_address,true)?:[];
 $olName=$olAddress['name']??($order->user->name??'Guest');
 $olPhone=$olAddress['phone']??($order->user->phone??'');
 $olProtection=$orderProtectionRows[$order->id]??null;
 $olViewRoute=$inhouseList?'inhouse_orders.show':'all_orders.show';
 $olLines=$order->orderDetails;
@endphp
<tr @if(!(bool)$order->viewed) class="order-unviewed" @endif>
 <td class="ol-select">@if(!$inhouseList)<label class="aiz-checkbox"><input type="checkbox" class="check-one" name="id[]" value="{{ $order->id }}" aria-label="Select order {{ \App\Services\OrderDisplay::label($order) }}"><span class="aiz-square-check"></span></label>@else<span class="ol-row-number">{{ $key+1+($orders->currentPage()-1)*$orders->perPage() }}</span>@endif</td>
 <td class="ol-identity"><div class="ol-order-top"><a class="ol-code" href="{{ route($olViewRoute,encrypt($order->id)) }}">{{ \App\Services\OrderDisplay::label($order) }}</a>@if($returningCustomers[$order->id]['returning']??false)<span class="ol-returning">Returning</span>@endif</div><time class="ol-time">{{ \App\Services\OrderTime::placed($order) }}</time><div class="ol-customer"><span>{{ $olName }}</span>@if($olPhone)<a href="tel:{{ $olPhone }}"><i class="las la-phone" aria-hidden="true"></i>{{ $olPhone }}</a>@else<small>No mobile recorded</small>@endif</div>
 @if($olProtection)<div class="ol-ip"><i class="las la-globe" aria-hidden="true"></i><span>IP: {{ $olProtection['ip']?:'Not recorded' }}</span>@if($olProtection['ip_blocked'])<span class="ol-blocked">Blocked</span>@endif</div><div class="ol-protection">@include('backend.sales.partials.order_block_actions',['blockOrder'=>$order,'hideMeta'=>true])</div>@endif
 </td>
 <td class="ol-products" data-label="Products">@foreach($olLines->take(3) as $line)@php($olProduct=$line->product)<div class="ol-product">@if($olProduct&&$olProduct->published)<a href="{{ route('product',$olProduct->slug) }}" target="_blank" rel="noopener" class="ol-photo" aria-label="View {{ $olProduct->name }}">@else<span class="ol-photo">@endif<img src="{{ $olProduct?\App\Services\OptimizedImages::asset($olProduct->thumbnail_img):static_asset('assets/img/placeholder.jpg') }}" alt="" width="43" height="57" loading="lazy" decoding="async" onerror="this.onerror=null;this.src='{{ static_asset('assets/img/placeholder.jpg') }}';">@if($olProduct&&$olProduct->published)</a>@else</span>@endif<div><strong>{{ $olProduct->name??'Unavailable product' }}</strong><small><span class="ol-size">Size: {{ $line->variation?:'Standard' }}</span><span>Qty {{ $line->quantity }}</span></small></div></div>@endforeach @if($olLines->count()>3)<a class="ol-more" href="{{ route($olViewRoute,encrypt($order->id)) }}">+{{ $olLines->count()-3 }} more products →</a>@endif</td>
 <td class="ol-amount" data-label="Amount"><strong>{{ single_price($order->grand_total) }}</strong><small>{{ $olLines->sum('quantity') }} item{{ $olLines->sum('quantity')===1?'':'s' }}</small></td>
 <td class="ol-delivery" data-label="Delivery"><span class="ol-status ol-status-{{ $order->delivery_status }}">{{ \App\Services\CourierOrderStatus::label($order) }}</span></td>
 <td class="ol-payment" data-label="Payment"><span class="ol-status {{ $order->payment_status==='paid'?'ol-status-delivered':'ol-status-unpaid' }}">{{ $order->payment_status==='paid'?'Paid':'Unpaid' }}</span><small>{{ ucwords(str_replace('_',' ',$order->payment_type)) }}</small></td>
 <td class="ol-actions"><div class="ol-history">@include('backend.sales.partials.courier_success',['courierButtonLabel'=>'History'])</div><a class="ol-view" href="{{ route($olViewRoute,encrypt($order->id)) }}" title="View invoice"><i class="las la-eye" aria-hidden="true"></i><span>View</span></a><button type="button" class="ol-delete" data-order-action="{{ route('admin.orders.destroy', $order->id) }}" data-order-token="{{ csrf_token() }}" title="Delete order" aria-label="Delete order" onclick="if (window.confirm(this.title + '?')) { const form = document.createElement('form'); form.method = 'POST'; form.action = this.dataset.orderAction; const token = document.createElement('input'); token.type = 'hidden'; token.name = '_token'; token.value = this.dataset.orderToken; form.append(token); document.body.append(form); form.submit(); }"><i class="las la-trash" aria-hidden="true"></i></button></td>
</tr>@empty<tr><td colspan="7"><div class="ol-empty"><i class="las la-shopping-bag" aria-hidden="true"></i><strong>No matching orders</strong><span>Try another filter or search.</span></div></td></tr>@endforelse</tbody></table>


