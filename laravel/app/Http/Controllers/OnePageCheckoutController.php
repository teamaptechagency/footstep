<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\OnePageCheckout;
class OnePageCheckoutController extends Controller {
    public function store(Request $request) {
        $data=OnePageCheckout::delivery($request);
        if (OnePageCheckout::carts($request)->isEmpty()) return redirect()->route('cart')->withErrors(['cart'=>'Your cart is empty.']);
        $request->merge($data+['payment_option'=>'cash_on_delivery']);
        $request->attributes->set('footstep_checkout',$data);
        $request->session()->put('footstep_checkout_cart_ids',OnePageCheckout::carts($request)->pluck('id')->all());
        $response=\Illuminate\Support\Facades\DB::transaction(function()use($request){
            $response=(new OrderController)->store($request);
            if($response instanceof \Illuminate\Http\RedirectResponse && $response->getTargetUrl()!==route('order_confirmed'))throw \Illuminate\Validation\ValidationException::withMessages(['order'=>'Order could not be placed. Please check stock and try again.']);
            return $response;
        });
        $result=$response instanceof \Symfony\Component\HttpFoundation\Response ? $response : redirect()->route('order_confirmed');
        if($result instanceof \Illuminate\Http\RedirectResponse&&$result->getTargetUrl()===route('order_confirmed')&&$request->session()->get('combined_order_id'))\App\Services\CheckoutDrafts::complete($request,$request->session()->get('combined_order_id'));

        if ($result instanceof \Illuminate\Http\RedirectResponse
            && $result->getTargetUrl() === route('order_confirmed')) {
            try {
                $combined = \App\Models\CombinedOrder::with('orders.orderDetails')
                    ->find($request->session()->get('combined_order_id'));
                $meta = app(\App\Services\MetaPixel::class);
                if ($combined && $meta->allowed($request)) {
                    $rows = [];
                    foreach ($combined->orders as $order) {
                        foreach ($order->orderDetails as $detail) {
                            $rows[] = ['id' => $detail->product_id, 'quantity' => $detail->quantity,
                                'price' => $detail->quantity > 0 ? convert_price($detail->price / $detail->quantity) : 0];
                        }
                    }
                    $data = \App\Services\MetaEventData::commerce($rows, \App\Services\MetaPixel::currency());
                    $data['value'] = round(convert_price($combined->grand_total), 2);
                    $data['order_id'] = (string) $combined->id;
                    $data['payment_method'] = 'cash_on_delivery';
                    $data['payment_status'] = 'unpaid';
                    $options = \App\Services\MetaPixel::settings();
                    \App\Services\MetaPixel::event($options['cod_event'], $data,
                        'cod-order-' . $combined->id, json_decode($combined->shipping_address, true) ?: [],
                        $combined->created_at->timestamp);
                } elseif ($combined) {
                    \Log::info('Meta checkout capture skipped by tracking preferences.', ['combined_order_id' => $combined->id]);
                }
            } catch (\Throwable $error) {
                \Log::warning('Meta checkout capture failed.', ['exception_type' => get_class($error)]);
            }
        }

        return $result;
    }

    // Edit one displayed pair without changing the other pairs in the cart row.
    public function item(Request $request) {
        $data=$request->validate(['id'=>'required|integer','unit'=>'required|integer|min:0','action'=>'required|in:size,remove','stock_id'=>'required_if:action,size|integer']);
        $carts=OnePageCheckout::carts($request);
        $item=$carts->firstWhere('id',(int)$data['id']);
        abort_unless($item,404);
        abort_if((int)$data['unit'] >= (int)$item->quantity,409,'Cart changed. Reload and try again.');
        if ($data['action']==='remove') {
            if ($item->quantity>1) {$item->quantity--; $item->save();} else $item->delete();
        } else {
            $product=$item->product;
            $stock=$product->stocks->firstWhere('id',(int)$data['stock_id']);
            abort_unless($stock,422,'Select a valid size.');
            if ((string)$stock->variant===(string)$item->variation) return response()->json(['ok'=>true]);
            $already=$carts->where('product_id',$item->product_id)->where('variation',$stock->variant)->sum('quantity');
            abort_if($stock->qty<$already+1,422,'This size is not available.');
            $price=(float)$stock->price;
            if ($product->wholesale_product) {
                $wholesale=$stock->wholesalePrices->where('min_qty','<=',1)->where('max_qty','>=',1)->first();
                if ($wholesale) $price=(float)$wholesale->price;
            }
            $discountActive=$product->discount_start_date===null || (time()>=$product->discount_start_date && time()<=$product->discount_end_date);
            if ($discountActive) {
                if ($product->discount_type==='percent') $price-=($price*$product->discount)/100;
                elseif ($product->discount_type==='amount') $price-=$product->discount;
            }
            $tax=0;
            foreach($product->taxes as $rule) $tax+=$rule->tax_type==='percent' ? $price*$rule->tax/100 : $rule->tax;
            if ($item->quantity>1) {
                $pair=$item->replicate();$item->quantity--;$item->save();
            } else $pair=$item;
            $pair->quantity=1;$pair->variation=$stock->variant;$pair->price=$price;$pair->tax=$tax;$pair->save();
        }
        // A cart edit invalidates any previously calculated coupon allocation.
        foreach(OnePageCheckout::carts($request) as $row) {
            $row->discount=0;$row->coupon_code=null;$row->coupon_applied=0;$row->save();
        }
        return response()->json(['ok'=>true]);
    }

    public function galleryOrder(Request $request) {
        $data=$request->validate(['product_id'=>'required|integer','flash_deal'=>'nullable|integer']);
        $product=filter_products(\App\Models\Product::where('published',1)->where('id',$data['product_id']))->with('stocks')->firstOrFail();
        $options=json_decode($product->choice_options ?? '[]',true) ?: [];
        $colors=json_decode($product->colors ?? '[]',true) ?: [];
        $productUrl=route('product',['slug'=>$product->slug,'flash_deal'=>$data['flash_deal']??null]);
        if(count($options)||count($colors)||$product->stocks->count()!==1||((string)$product->stocks->first()->variant)!=='')return redirect($productUrl.'#option-choice-form');
        $qty=max(1,(int)$product->min_qty);
        if($product->stocks->first()->qty<$qty)return redirect($productUrl)->withErrors(['product'=>'This product is currently unavailable.']);
        $request->merge(['id'=>$product->id,'quantity'=>$qty]);
        $result=(new CartController)->addToCart($request);
        if(($result['status']??0)==1)return redirect()->route('cart');
        return redirect($productUrl)->withErrors(['product'=>'Could not order this product. Please select the options and try again.']);
    }
}
