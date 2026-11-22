@php
 $customerCanEdit=\App\Services\CustomerInvoice::editable($order);
 $customerInvoiceUrl=\Illuminate\Support\Facades\URL::temporarySignedRoute('invoice.shared',now()->addDays(7),['id'=>$order->id]);
 $purchasedByLine=collect(\App\Services\ProductAddons::lines($order->id))->groupBy('order_detail_id');
 $invoiceAddonOptions=[];
 foreach($order->orderDetails as $detail){
  $purchased=$purchasedByLine->get($detail->id,collect());
  $options=collect(\App\Services\ProductAddons::available($detail->product_id))->keyBy('id');
  foreach($purchased as $snapshot)$options->put((int)$snapshot->addon_id,['id'=>(int)$snapshot->addon_id,'name'=>$snapshot->name,'price'=>(float)$snapshot->unit_price]);
  $invoiceAddonOptions[$detail->id]=$options;
 }
 $hasInvoiceAddons=collect($invoiceAddonOptions)->contains(function($options){return $options->isNotEmpty();});
@endphp
@if($customerCanEdit&&$hasInvoiceAddons)
<section class="customer-invoice-addons" style="max-width:600px;width:100%;box-sizing:border-box;text-align:left;margin:16px auto;padding:16px;border:1px solid #dbe8e0;border-radius:10px;background:#f7fbf8;margin-bottom:16px"><h3 style="font-size:15px;margin:0 0 12px">Add-ons / সঙ্গে নিতে পারেন</h3>
<form class="customer-invoice-addon-form" method="POST" action="{{ $customerInvoiceUrl }}">@csrf
@foreach($order->orderDetails as $customerLine)
@php
 $currentStock=optional($customerLine->product)->stocks?optional($customerLine->product)->stocks->first(function($stock)use($customerLine){return (string)$stock->variant===(string)$customerLine->variation;}):null;
 $lineOptions=$invoiceAddonOptions[$customerLine->id];
 $lineChosen=old('lines.'.$customerLine->id.'.addons_present')==='1'?old('lines.'.$customerLine->id.'.addon_ids',[]):$purchasedByLine->get($customerLine->id,collect())->pluck('addon_id')->all();
 $lineChosen=array_map('intval',$lineChosen);
@endphp
<input type="hidden" name="lines[{{ $customerLine->id }}][stock_id]" value="{{ $currentStock->id??0 }}">
<input type="hidden" name="lines[{{ $customerLine->id }}][quantity]" value="{{ $customerLine->quantity }}">
<input type="hidden" name="lines[{{ $customerLine->id }}][addons_present]" value="1">
@if($lineOptions->isNotEmpty())<fieldset data-invoice-addons style="min-width:0;border:0;margin:0 0 12px;padding:0">
@if($order->orderDetails->count()>1)<legend style="font-size:12px;font-weight:700">{{ optional($customerLine->product)->name }}</legend>@endif
@foreach($lineOptions as $lineAddon)<label style="display:flex;align-items:flex-start;gap:8px;margin:8px 0;font-size:13px;line-height:1.6"><input type="checkbox" name="lines[{{ $customerLine->id }}][addon_ids][]" value="{{ $lineAddon['id'] }}" @if(in_array((int)$lineAddon['id'],$lineChosen,true)) checked @endif style="margin-top:4px;flex-shrink:0"><span style="flex:1;min-width:0">{{ $lineAddon['name'] }}</span><strong style="white-space:nowrap">+৳{{ number_format($lineAddon['price'],2) }}</strong></label>@endforeach
</fieldset>@endif
@endforeach
<button type="submit" style="padding:12px 16px;border:0;border-radius:7px;background:#087c46;color:white;font-weight:bold;cursor:pointer">Save add-ons / যোগ করুন</button>
</form></section>@endif
<style>@media print{.customer-invoice-addons{display:none!important}}</style>
