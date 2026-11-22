<?php
namespace App\Services;

class CourierLabel
{
    public static function description($order)
    {
        return mb_substr($order->orderDetails->map(function ($line) {
            return ($line->product_name_snapshot ?: ($line->product->name ?? 'Product')).' | Size: '.($line->variation ?: '—').' | Qty: '.$line->quantity;
        })->implode('; '), 0, 1000);
    }

    public static function data($order)
    {
        $s = StoreOperations::shipment($order->id) ?: [];
        $a = json_decode($order->shipping_address, true) ?: [];
        return $s + ['recipient_name'=>$a['name']??'', 'recipient_phone'=>$a['phone']??'',
            'recipient_address'=>implode(', ',array_filter([$a['address']??'',$a['city']??''])),
            'cod_amount'=>$order->payment_status==='paid'?0:$order->grand_total,
            'item_description'=>self::description($order)];
    }
}
