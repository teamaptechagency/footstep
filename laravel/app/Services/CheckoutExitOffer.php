<?php
namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class CheckoutExitOffer
{
    public static function issue($productId,$checkoutToken,array $addons)
    {
        $addons=array_slice($addons,0,10);if(!$addons)return null;
        $payload=['purpose'=>'checkout-exit-free-v1','product_id'=>(int)$productId,'checkout_token'=>$checkoutToken,'addon_ids'=>array_column($addons,'id'),'expires_at'=>now()->addHour()->timestamp];
        return ['token'=>Crypt::encryptString(json_encode($payload)),'addons'=>$addons];
    }
    public static function resolve(Request $request,$productId)
    {
        $input=$request->validate(['exit_offer_token'=>'nullable|string|max:4096']);
        $token=$input['exit_offer_token']??null;if(!$token)return [];
        try{$payload=json_decode(Crypt::decryptString($token),true);}catch(\Throwable $e){$payload=null;}
        if(!is_array($payload)||($payload['purpose']??'')!=='checkout-exit-free-v1'||(int)($payload['product_id']??0)!==(int)$productId||!is_string($payload['checkout_token']??null)||!is_string($request->input('checkout_token'))||!hash_equals($payload['checkout_token'],$request->input('checkout_token'))||(int)($payload['expires_at']??0)<=now()->timestamp||!is_array($payload['addon_ids']??null)){
            throw ValidationException::withMessages(['exit_offer_token'=>'Free add-on offer-এর মেয়াদ শেষ বা তথ্য সঠিক নয়। পেজ refresh করে Popup নতুন করে খুলুন।']);
        }
        $allowed=array_column(ProductAddons::available($productId),'id');
        $ids=array_values(array_intersect(ProductAddons::ids($payload['addon_ids']),$allowed));
        if(!$ids)throw ValidationException::withMessages(['exit_offer_token'=>'এই product-এর free add-on এখন আর পাওয়া যাচ্ছে না।']);
        return $ids;
    }
    // Only DirectCheckoutController sets this trusted attribute after verifying the token.
    public static function forCart(Request $request,$cart)
    {
        $offer=$request->attributes->get('footstep_exit_offer',[]);
        return is_array($offer)&&(int)($offer['product_id']??0)===(int)$cart['product_id']&&(int)($offer['cart_id']??0)===(int)$cart['id']?($offer['ids']??[]):[];
    }
}
