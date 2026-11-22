<?php

namespace App\Http\Controllers\Api\V2;

use Illuminate\Http\Request;

class PaymentTypesController
{
    public function getList(Request $request)
    {
        if ($request->input('mode', 'order') !== 'order') {
            return response()->json([]);
        }
        return response()->json([[
            'payment_type' => 'cash_payment',
            'payment_type_key' => 'cash_on_delivery',
            'image' => static_asset('assets/img/cards/cod.png'),
            'name' => 'Cash on Delivery',
            'title' => 'Cash on delivery',
            'offline_payment_id' => 0,
            'details' => '',
        ]]);
    }
}