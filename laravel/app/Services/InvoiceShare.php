<?php
namespace App\Services;

class InvoiceShare
{
    public static function data($order)
    {
        $address = json_decode($order->shipping_address, true) ?: [];
        $addons = ProductAddons::lines($order->id);
        $lines = [];
        foreach ($order->orderDetails as $line) {
            $product = $line->product;
            $photo = null;
            if ($product) {
                // Follow the saved gallery order, rather than the card thumbnail.
                $photos = explode(',', (string) $product->photos);
                $first = trim($photos[0] ?? '');
                if (ctype_digit($first) && (int) $first > 0) {
                    $photo = OptimizedImages::asset((int) $first);
                }
            }
            $lines[] = [
                'name' => $product ? (string) $product->name : ($line->product_name_snapshot ?: 'Product unavailable'),
                'size' => $line->variation ?: 'Standard',
                'quantity' => (int) $line->quantity,
                'unitPrice' => number_format($line->quantity ? $line->price / $line->quantity : 0, 2, '.', ','),
                'total' => number_format($line->price, 2, '.', ','),
                'photo' => $photo ?: InvoiceImages::source($product),
            ];
        }
        return [
            'order' => (string) OrderDisplay::label($order),
            'date' => (string) OrderTime::placed($order),
            'customer' => array_values(array_filter([
                (string) ($address['name'] ?? ''), (string) ($address['phone'] ?? ''),
                (string) ($address['address'] ?? ''), (string) ($address['city'] ?? ''),
            ], function ($value) { return $value !== ''; })),
            'status' => ucfirst(str_replace('_', ' ', $order->delivery_status)),
            'payment' => ucfirst($order->payment_status),
            'method' => str_replace('_', ' ', $order->payment_type),
            'currency' => MetaPixel::currency(),
            'lines' => $lines,
            'addons' => $addons->map(function ($addon) {
                return [
                    'name' => (string) $addon->name, 'quantity' => (int) $addon->quantity,
                    'total' => number_format($addon->total, 2, '.', ','),
                    'free' => (float) $addon->unit_price === 0.0,
                    'saving' => number_format(max(0, (float) $addon->regular_price) * (int) $addon->quantity, 2, '.', ','),
                ];
            })->values()->all(),
            'totals' => [
                'Subtotal' => number_format($order->orderDetails->sum('price') + $addons->sum('total'), 2, '.', ','),
                'Delivery' => number_format($order->orderDetails->sum('shipping_cost'), 2, '.', ','),
                'Tax' => number_format($order->orderDetails->sum('tax'), 2, '.', ','),
                'Discount' => number_format($order->coupon_discount, 2, '.', ','),
                'Total' => number_format($order->grand_total, 2, '.', ','),
            ],
        ];
    }
}
