@include('frontend.partials.invoice_responsive')
@php
 $address=json_decode($order->shipping_address,true)?:[];
  $invoiceAddons=\App\Services\ProductAddons::lines($order->id);
 $currency=\App\Services\MetaPixel::currency();
@endphp
<section class="order-invoice-document"><header style="border-bottom:2px solid #14774e;padding:20px 0;display:flex;justify-content:space-between"><div><h2 style="margin:0">FOOTSTEP ONLINE</h2><small>Order invoice / অর্ডার ইনভয়েস</small></div><div><strong>#{{ \App\Services\OrderDisplay::label($order) }}</strong><br>{{ \App\Services\OrderTime::placed($order) }}</div></header>
@include('frontend.partials.returning_customer_note',['returningOrder'=>$order])
<div style="display:flex;justify-content:space-between;padding:24px 0"><address style="font-style:normal"><strong>{{ $address['name']??'' }}</strong><br>{{ $address['phone']??'' }}<br>{{ $address['address']??'' }}<br>{{ $address['city']??'' }}</address><div>Order status: <strong>{{ \App\Services\CourierOrderStatus::label($order) }}</strong><br>Payment: {{ ucfirst($order->payment_status) }}<br>Method: {{ str_replace('_',' ',$order->payment_type) }}</div></div>
<table style="width:100%;border-collapse:collapse"><thead><tr style="background:#f2f5f3"><th style="padding:12px;text-align:left">#</th><th style="text-align:left">Photo</th><th style="text-align:left">Product / size</th><th>Qty</th><th style="text-align:right">Unit price</th><th style="text-align:right">Total</th></tr></thead><tbody>@foreach($order->orderDetails as $line)@php $invoiceProductUrl=optional($line->product)->slug ? route('product',['slug'=>$line->product->slug]) : null; @endphp<tr style="border-bottom:1px solid #e0e7e3"><td style="padding:14px 8px">{{ $loop->iteration }}</td><td style="padding:10px 6px;width:58px">@if($invoiceProductUrl)<a href="{{ $invoiceProductUrl }}" target="_blank" rel="noopener" aria-label="View product">@endif<img src="{{ \App\Services\InvoiceImages::source($line->product,!empty($invoicePdf)) }}" alt="Product photo" width="48" height="64" style="display:block;width:48px;height:64px;object-fit:cover;border-radius:4px">@if($invoiceProductUrl)</a>@endif</td><td>@if($invoiceProductUrl)<a href="{{ $invoiceProductUrl }}" target="_blank" rel="noopener" style="color:inherit;text-decoration:underline">{{ $line->product->name }}</a>@else {{ $line->product_name_snapshot ?: 'Product unavailable' }} @endif<br><small>Size: {{ $line->variation?:'Standard' }}</small></td><td style="text-align:center">{{ $line->quantity }}</td><td style="text-align:right">{{ number_format($line->quantity?$line->price/$line->quantity:0,2) }}</td><td style="text-align:right">{{ number_format($line->price,2) }}</td></tr>@endforeach
@foreach($invoiceAddons as $invoiceAddon)
@php
 $invoiceAddonIsFree=(float)$invoiceAddon->unit_price===0.0;
 $invoiceAddonOfferSavings=$invoiceAddonIsFree ? max(0,(float)$invoiceAddon->regular_price)*(int)$invoiceAddon->quantity : 0;
@endphp
<tr style="border-bottom:1px solid #e0e7e3">
 <td>+</td><td>🧦</td>
 <td style="padding:12px 0">
  {{ $invoiceAddon->name }}
  @if($invoiceAddonIsFree)
   <br><strong data-invoice-free-gift style="display:inline-block;margin:5px 0;padding:3px 8px;border:1px solid #b7dfc6;border-radius:5px;background:#edf9f1;color:#17633d;font-size:11px">Free gift / অফার</strong>
   @if($invoiceAddonOfferSavings>0)
    <br><small style="color:#17633d">Offer discount / অফার ছাড়: {{ number_format($invoiceAddonOfferSavings,2) }} {{ $currency }}</small>
   @endif
  @else
   <br><small>Add-on · Regular {{ number_format($invoiceAddon->regular_price,2) }} {{ $currency }}</small>
  @endif
 </td>
 <td style="text-align:center">{{ $invoiceAddon->quantity }}</td>
 <td style="text-align:right">
  @if($invoiceAddonIsFree && (float)$invoiceAddon->regular_price>0)
   <del style="display:block;color:#7b8580;font-size:11px">{{ number_format($invoiceAddon->regular_price,2) }}</del>
  @endif
  {{ number_format($invoiceAddon->unit_price,2) }}
 </td>
 <td style="text-align:right">
  @if($invoiceAddonIsFree)<strong style="color:#17633d">0.00</strong>@else{{ number_format($invoiceAddon->total,2) }}@endif
 </td>
</tr>
@endforeach</tbody></table>
<div style="text-align:right;line-height:2;padding-top:16px">Subtotal: {{ number_format($order->orderDetails->sum('price')+$invoiceAddons->sum('total'),2) }} {{ $currency }}<br>Delivery: {{ number_format($order->orderDetails->sum('shipping_cost'),2) }}<br>Tax: {{ number_format($order->orderDetails->sum('tax'),2) }}<br>Discount: {{ number_format($order->coupon_discount,2) }}<br><strong style="font-size:22px">Total: {{ number_format($order->grand_total,2) }} {{ $currency }}</strong></div>
@if($order->payment_type==='cash_on_delivery'&&$order->payment_status!=='paid')<p>Cash on Delivery — পণ্য হাতে পেয়ে মূল্য পরিশোধ করুন।</p>@endif
<p style="border-top:1px solid #ddd;padding-top:18px">Thank you for choosing Footstep / আমাদের সঙ্গে থাকার জন্য ধন্যবাদ।</p></section>
