<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use App\Services\OnePageCheckout;
use App\Models\Cart;
class DirectCheckoutController extends Controller
{
    public function quick(Request $request,$id)
    {
        $detailedProduct = filter_products(\App\Models\Product::where('published',1)->whereKey($id))->with(['stocks','taxes'])->firstOrFail();
        abort_if($detailedProduct->external_link,422);
        if ($request->boolean('checkout_meta')) {
            if (!app(\App\Services\MetaPixel::class)->allowed($request)) {
                return response()->json(['config' => ['enabled' => false], 'events' => []])
                    ->header('Cache-Control', 'private, no-store');
            }
            return response()->view('frontend.partials.meta_pixel', [
                'detailedProduct' => $detailedProduct,
                'checkoutMetaJson' => true,
            ])->header('Content-Type', 'application/json')->header('Cache-Control', 'private, no-store');
        }
        $qty=max(max(1,(int)$detailedProduct->min_qty),min(99,(int)$request->query('quantity',1)));
        $variant=$request->query('variant');
        $stock=$variant!==null?$detailedProduct->stocks->firstWhere('variant',(string)$variant):$detailedProduct->stocks->first(function($s)use($qty){return $s->qty>=$qty;});
        $stock=$stock&&$stock->qty>=$qty?$stock:null;
        $initialQuote=$stock?$this->quoteData($this->pricedSelection($detailedProduct,$stock,$qty)):null;
        $addonIds=\App\Services\ProductAddons::ids($request->query('addon_ids',[]));
        if($initialQuote){$addons=\App\Services\ProductAddons::quote($id,$addonIds,$qty);$initialQuote['addons']=$addons;$initialQuote['subtotal']+=$addons['total'];}
        return response()->view('frontend.partials.card_checkout',compact('detailedProduct','initialQuote'))->header('Cache-Control','private, no-store')->header('X-Frame-Options','SAMEORIGIN');
    }
    private function selection(Request $request)
    {
        return \App\Services\Checkout\VariantSelection::fromRequest($request);
    }
    private function pricedSelection($product, $stock, $qty)
    {
        return \App\Services\Checkout\ProductQuote::selection($product, $stock, $qty);
    }
    private function quoteData($s)
    {
        return ['stock_id' => $s['stock']->id, 'variant' => $s['stock']->variant, 'quantity' => $s['qty'], 'unit_price' => $s['price'], 'subtotal' => ($s['price'] + $s['tax']) * $s['qty'], 'fees' => OnePageCheckout::fees($s['qty'])];
    }
    public function quote(Request $request)
    {
        $s = $this->selection($request);
        $quote=$this->quoteData($s);$addons=\App\Services\ProductAddons::quote($s['product']->id,$request->input('addon_ids',[]),$s['qty'],true,\App\Services\CheckoutExitOffer::resolve($request,$s['product']->id));$quote['addons']=$addons;$quote['subtotal']+=$addons['total'];return response()->json($quote);
    }
    public function store(Request $request)
    {
        $request->validate(['checkout_token' => 'required|string|size:32']);
        $key = 'direct_checkout.' . $request->input('checkout_token');
        if ($request->session()->has($key)) {
            $request->session()->put('combined_order_id', $request->session()->get($key));
            return response()->json(['redirect' => route('order_confirmed')]);
        }
        OnePageCheckout::delivery($request);
        $s = $this->selection($request);
        if (!$request->user() && !$request->session()->has('temp_user_id')) {
            $request->session()->put('temp_user_id', bin2hex(random_bytes(10)));
        }
        $cart = new Cart();
        $cart->user_id = $request->user() ? $request->user()->id : null;
        $cart->temp_user_id = $request->user() ? null : $request->session()->get('temp_user_id');
        $cart->owner_id = \App\Services\SingleVendor::ownerId();
        $cart->product_id = $s['product']->id;
        $cart->variation = $s['stock']->variant;
        $cart->quantity = $s['qty'];
        $cart->price = $s['price'];
        $cart->tax = $s['tax'];
        $cart->shipping_cost = 0;
        $cart->shipping_type = 'home_delivery';
        $cart->discount = 0;
        $addonIds=\App\Services\ProductAddons::ids($request->input('addon_ids',[]));
        $exitFreeIds=\App\Services\CheckoutExitOffer::resolve($request,$s['product']->id);
        \App\Services\ProductAddons::quote($s['product']->id,$addonIds,$s['qty'],true,$exitFreeIds);
        $cart->addon_ids=json_encode($addonIds);
        $cart->save();
        $request->attributes->set('footstep_exit_offer',['product_id'=>(int)$s['product']->id,'cart_id'=>(int)$cart->id,'ids'=>$exitFreeIds]);
        $request->attributes->set('footstep_cart_ids', [$cart->id]);
        try {
            $response = (new OnePageCheckoutController())->store($request);
            $combinedId = $request->session()->get('combined_order_id');
            if (!$combinedId || !$response instanceof \Illuminate\Http\RedirectResponse || $response->getTargetUrl() !== route('order_confirmed')) {
                throw new \RuntimeException('Order could not be completed.');
            }
            $request->session()->put($key, $combinedId);
            return response()->json(['redirect' => route('order_confirmed')]);
        } finally {
            $cart->delete();
        }
    }
}
