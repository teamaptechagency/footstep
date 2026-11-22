<?php

namespace App\Services\Checkout;

/** One calculation shared by quick HTML, quotes and order submission. */
class ProductQuote
{
    public static function selection($product, $stock, $qty)
    {
        $price = (float) $stock->price;
        if ($product->wholesale_product) {
            $wholesale = $stock->wholesalePrices->where('min_qty', '<=', $qty)->where('max_qty', '>=', $qty)->first();
            if ($wholesale) {
                $price = (float) $wholesale->price;
            }
        }
        if ($product->discount_start_date === null || time() >= $product->discount_start_date && time() <= $product->discount_end_date) {
            if ($product->discount_type === 'percent') {
                $price -= $price * $product->discount / 100;
            } elseif ($product->discount_type === 'amount') {
                $price -= $product->discount;
            }
        }
        $tax = 0;
        foreach ($product->taxes as $rule) {
            $tax += $rule->tax_type === 'percent' ? $price * $rule->tax / 100 : $rule->tax;
        }
        return compact('product', 'stock', 'qty', 'price', 'tax');
    }
}
