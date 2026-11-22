@php
$metaService=app(\App\Services\MetaPixel::class);
$metaOptions=\App\Services\MetaPixel::settings();
$metaConfig=$metaService->browserConfig(request());
$metaEvents=[];
if($metaConfig['enabled']) {
    $metaEvents[]=\App\Services\MetaPixel::event('PageView');
    if(isset($detailedProduct)) {
        $data=\App\Services\MetaEventData::commerce([['id'=>$detailedProduct->id,'quantity'=>1,'price'=>home_discounted_base_price($detailedProduct,false)]],\App\Services\MetaPixel::currency());
        $data['content_name']=mb_substr($detailedProduct->name,0,200);
        $metaEvents[]=\App\Services\MetaPixel::event('ViewContent',$data);
    }
    if(isset($query)&&is_string($query)&&trim($query)!=='')$metaEvents[]=\App\Services\MetaPixel::event('Search',['search_string'=>\App\Services\MetaEventData::searchTerm($query)]);
    if(isset($carts)&&count($carts)>0&&request()->is('cart','checkout/*')) {
        $rows=[];foreach($carts as $cart)$rows[]=['id'=>$cart['product_id'],'quantity'=>$cart['quantity'],'price'=>convert_price($cart['price'])];
        $cartData=\App\Services\MetaEventData::commerce($rows,\App\Services\MetaPixel::currency());
        $metaEvents[]=\App\Services\MetaPixel::event('InitiateCheckout',$cartData);
        $metaConfig['payment_event']=\App\Services\MetaPixel::event('AddPaymentInfo',$cartData+['payment_method'=>'cash_on_delivery'],null,[],null,false);
    }
    if(isset($combined_order)&&request()->is('order-confirmed')) {
        $rows=[];foreach($combined_order->orders as $order)foreach($order->orderDetails as $detail)$rows[]=['id'=>$detail->product_id,'quantity'=>$detail->quantity,'price'=>$detail->quantity>0?convert_price($detail->price/$detail->quantity):0];
        $data=\App\Services\MetaEventData::commerce($rows,\App\Services\MetaPixel::currency());$data['value']=round(convert_price($combined_order->grand_total),2);$data['order_id']=(string)$combined_order->id;$data['payment_method']='cash_on_delivery';$data['payment_status']='unpaid';
        $matching=json_decode($combined_order->shipping_address,true)?:[];
        $metaEvents[]=\App\Services\MetaPixel::event($metaOptions['cod_event'],$data,'cod-order-'.$combined_order->id,$matching,$combined_order->created_at?$combined_order->created_at->timestamp:time());
    }
    foreach(session()->pull('meta_after_redirect',[]) as $event)$metaEvents[]=$event;
    $metaConfig['contact_event']=\App\Services\MetaPixel::event('Contact',['content_name'=>'WhatsApp / Messenger'],null,[],null,false);
}
$metaConfig['endpoint']=route('meta.event');$metaConfig['consent_endpoint']=route('meta.consent');$metaConfig['csrf']=csrf_token();
@endphp
@if(!empty($checkoutMetaJson))
@php
$metaConfig['endpoint']=route('meta.next.event',[],false);
$metaConfig['consent_endpoint']=route('meta.next.consent',[],false);
$checkoutMetaPayload=['config'=>$metaConfig,'events'=>array_values(array_filter($metaEvents)),
    'script'=>static_asset('assets/js/meta-pixel.js').'?v='.filemtime(public_path('assets/js/meta-pixel.js'))];
@endphp
{!! json_encode($checkoutMetaPayload, JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE) !!}
@elseif($metaConfig['enabled'])
<script>window.FootstepMetaConfig=@json($metaConfig);window.FootstepMetaEvents=@json(array_values(array_filter($metaEvents)));</script>
<script defer src="{{ static_asset('assets/js/meta-pixel.js') }}?v={{ filemtime(public_path('assets/js/meta-pixel.js')) }}"></script>
@endif
