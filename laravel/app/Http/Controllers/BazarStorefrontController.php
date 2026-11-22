<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class BazarStorefrontController extends Controller {
    public function cart(Request $request) {
        $cart=\App\Services\OnePageCheckout::carts($request);
        return response()->view('frontend.themes.bazar.cart_drawer',['bazarCart'=>$cart])->header('Cache-Control','private, no-store')->header('X-Cart-Count',(string)$cart->count())->header('X-Cart-Total',rawurlencode(single_price(\App\Services\OnePageCheckout::base($cart)['base'])));
    }
}
