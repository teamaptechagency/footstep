<?php

namespace App\Services\Checkout;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
/** Resolve fresh product/stock data on every quote and confirmation. */
class VariantSelection
{
    public static function fromRequest(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'quantity' => 'required|integer|min:1|max:99', 'stock_id' => 'nullable|integer']);
        $product = filter_products(\App\Models\Product::where('published', 1)->where('id', $data['product_id']))->with(['stocks', 'taxes'])->firstOrFail();
        if ($product->external_link) {
            throw ValidationException::withMessages(['product' => 'Use the external product order link.']);
        }
        if (!empty($data['stock_id'])) {
            $stock = $product->stocks->firstWhere('id', (int) $data['stock_id']);
        } else {
            $parts = [];
            if ($request->filled('color')) {
                $parts[] = (string) $request->input('color');
            }
            foreach (json_decode($product->choice_options ?? '[]') ?: [] as $choice) {
                $value = $request->input('attribute_id_' . $choice->attribute_id);
                if (!is_string($value) || $value === '') {
                    throw ValidationException::withMessages(['size' => 'Please select a size.']);
                }
                $parts[] = str_replace(' ', '', $value);
            }
            $stock = $product->stocks->firstWhere('variant', implode('-', $parts));
        }
        if (!$stock) {
            throw ValidationException::withMessages(['size' => 'Please select an available size.']);
        }
        $qty = (int) $data['quantity'];
        if ($qty < max(1, (int) $product->min_qty) || $stock->qty < $qty) {
            throw ValidationException::withMessages(['quantity' => 'The selected size or quantity is not available.']);
        }
        return ProductQuote::selection($product, $stock, $qty);
    }
}
