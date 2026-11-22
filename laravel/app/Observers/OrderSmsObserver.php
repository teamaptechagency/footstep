<?php
namespace App\Observers;
use App\Models\Order;
use App\Services\OrderSms;

class OrderSmsObserver
{
    public function created(Order $order)
    {
        OrderSms::enqueue($order, 'placed');
        if ($order->delivery_status === 'confirmed') OrderSms::enqueue($order, 'confirmed');
    }
    public function updated(Order $order)
    {
        if ($order->wasChanged('delivery_status') && $order->delivery_status === 'confirmed') {
            OrderSms::enqueue($order, 'confirmed');
        }
    }
}
