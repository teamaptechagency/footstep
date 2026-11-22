<?php
namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Review;
use App\Services\Checkout\ProductQuote;
use App\Services\StorefrontV3Catalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Browser-session interactions; account identity and all prices stay server-owned. */
class ProductInteractionController extends Controller
{
    private function product($id)
    {
        return StorefrontV3Catalog::query()->whereKey((int) $id)->firstOrFail();
    }
    private function output($data, $status = 200)
    {
        return response()->json($data, $status)->header('Cache-Control', 'private, no-store');
    }
    private function eligible($user, $id)
    {
        return $user && $user->user_type === 'customer' && OrderDetail::where('product_id', $id)
            ->whereHas('order', function ($q) use ($user) {
                $q->where('user_id', $user->id)->where('delivery_status', '<>', 'cancelled');
            })->where(function ($q) {
                $q->where('delivery_status', 'delivered')->orWhereHas('order', function ($o) { $o->where('delivery_status', 'delivered'); });
            })->exists();
    }
    private function cartQuery(Request $request)
    {
        if ($request->user()) return Cart::where('user_id', $request->user()->id);
        $temp = $request->session()->get('temp_user_id');
        return $temp ? Cart::whereNull('user_id')->where('temp_user_id', $temp) : Cart::whereRaw('1=0');
    }
    public function session(Request $request, $id)
    {
        $this->product($id); $user = $request->user();
        $existing = $user && Review::where('product_id', $id)->where('user_id', $user->id)->exists();
        return $this->output(['csrf_token' => csrf_token(), 'authenticated' => (bool) $user,
            'customer_name' => $user ? $user->name : null,
            'can_review' => !$existing && $this->eligible($user, $id), 'already_reviewed' => (bool) $existing,
            'cart_count' => (int) $this->cartQuery($request)->sum('quantity'), 'login_url' => '/users/login']);
    }
    public function review(Request $request, $id)
    {
        if (!$request->user()) return $this->output(['message' => 'Review দিতে আগে login করুন।'], 401);
        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'comment' => 'required|string|min:5|max:3000']);
        $product = $this->product($id);
        if (!$this->eligible($request->user(), $product->id)) return $this->output(['message' => 'এই product-এর delivered order থাকলে review দিতে পারবেন।'], 403);
        DB::transaction(function () use ($request, $product, $data) {
            Product::whereKey($product->id)->lockForUpdate()->firstOrFail();
            if (Review::where('product_id', $product->id)->where('user_id', $request->user()->id)->exists())
                throw ValidationException::withMessages(['review' => 'এই product-এ আপনার review আগে জমা হয়েছে।']);
            $review = new Review(); $review->product_id = $product->id; $review->user_id = $request->user()->id;
            $review->rating = $data['rating']; $review->comment = trim($data['comment']);
            $review->status = 0; $review->viewed = 0; $review->save();
        });
        return $this->output(['message' => 'Review জমা হয়েছে। Admin অনুমোদনের পরে দেখা যাবে।', 'pending' => true], 201);
    }
    public function cart(Request $request, $id)
    {
        $data = $request->validate(['variant' => 'nullable|string|max:255', 'quantity' => 'required|integer|min:1|max:99', 'addon_ids'=>'nullable|array|max:10','addon_ids.*'=>'integer|min:1']);
        $product = $this->product($id); $variant = (string) ($data['variant'] ?? '');
        $addonIds=\App\Services\ProductAddons::ids($data['addon_ids']??[]);\App\Services\ProductAddons::quote($id,$addonIds,(int)$data['quantity']);
        $quantity = (int) $data['quantity']; $minimum = max(1, (int) $product->min_qty);
        $stock = $product->stocks->firstWhere('variant', $variant);
        if (!$stock || $stock->qty < $quantity || $quantity < $minimum)
            throw ValidationException::withMessages(['variant' => 'Available size ও সঠিক quantity বাছুন।']);
        if (!$request->user() && !$request->session()->get('temp_user_id')) $request->session()->put('temp_user_id', bin2hex(random_bytes(10)));
        DB::transaction(function () use ($request, $product, $stock, $variant, $quantity, $addonIds) {
            $locked = $product->stocks()->whereKey($stock->id)->lockForUpdate()->firstOrFail();
            $cart = $this->cartQuery($request)->where('product_id', $product->id)->where('variation', $variant)->where(function($q)use($addonIds){$q->where('addon_ids',json_encode($addonIds));if(!$addonIds)$q->orWhereNull('addon_ids');})->lockForUpdate()->first();
            $total = ($cart ? $cart->quantity : 0) + $quantity;
            if ($this->cartQuery($request)->where('product_id',$product->id)->where('variation',$variant)->sum('quantity')+$quantity > $locked->qty || $total > 99) throw ValidationException::withMessages(['quantity' => 'এই size-এর stock-এর বেশি quantity দেওয়া যাবে না।']);
            $quote = ProductQuote::selection($product, $locked, $total);
            $cart = $cart ?: new Cart(); $cart->product_id = $product->id; $cart->owner_id = $product->user_id;
            $cart->user_id = $request->user() ? $request->user()->id : null;
            $cart->temp_user_id = $request->user() ? null : $request->session()->get('temp_user_id');
            $cart->variation = $variant; $cart->quantity = $total; $cart->price = $quote['price']; $cart->tax = $quote['tax'];
            $cart->addon_ids=json_encode($addonIds);$cart->shipping_type = 'home_delivery'; $cart->save();
        });
        return $this->output(['message' => 'কার্টে যোগ হয়েছে।', 'cart_count' => (int) $this->cartQuery($request)->sum('quantity'), 'cart_url' => '/cart', 'meta_event'=>\App\Services\MetaPixel::event('AddToCart',\App\Services\MetaEventData::commerce([['id'=>$product->id,'quantity'=>$quantity,'price'=>ProductQuote::selection($product,$stock,$quantity)['price']]],\App\Services\MetaPixel::currency()))]);
    }
}
