<?php
namespace App\Services;
use App\Models\Cart;
class OnePageCheckout {
    const FEES = ['In Dhaka City'=>100, 'Out Of Dhaka City'=>150];
    // Quantity counts shoes/products only, never optional add-ons.
    public static function fee($city, $quantity=1) {
        $quantity=max(1,(int)$quantity);
        return $quantity>1 ? $quantity*100 : (self::FEES[$city]??0);
    }
    public static function fees($quantity=1) {
        return array_map(function($city)use($quantity){return self::fee($city,$quantity);},array_combine(array_keys(self::FEES),array_keys(self::FEES)));
    }
    public static function carts($request) {
        $query=Cart::with('product.stocks');
        if ($request->user()) $query->where('user_id',$request->user()->id);
        elseif ($request->session()->get('temp_user_id')) $query->where('temp_user_id',$request->session()->get('temp_user_id'));
        else $query->whereRaw('1=0');
        if($request->attributes->has('footstep_cart_ids')) $query->whereIn('id',$request->attributes->get('footstep_cart_ids'));
        return $query->get();
    }
    public static function delivery($request) {
        $request->merge(['phone'=>self::phone((string)$request->input('phone')),'whatsapp_phone'=>self::phone((string)$request->input('whatsapp_phone'))]);
        $data=$request->validate([
            'name'=>'required|string|max:191','phone'=>['required','regex:/^01[3-9][0-9]{8}$/'],
            'address'=>'required|string|max:255','city'=>['required',\Illuminate\Validation\Rule::in(array_keys(self::FEES))],
            'whatsapp_same'=>'nullable|boolean','whatsapp_phone'=>['nullable','regex:/^01[3-9][0-9]{8}$/'],
            'email'=>'nullable|email|max:191','payment_option'=>'nullable|in:cash_on_delivery'
        ]);
        \App\Services\OrderProtection::assertAllowed($request,$data['phone']);
        $data['email']=$data['email'] ?? ($request->user()->email ?? '');
        $data['whatsapp_same']=$request->boolean('whatsapp_same')?'1':'0';
        $data['whatsapp_phone']=$data['whatsapp_same']==='1'?$data['phone']:($data['whatsapp_phone']??'');
        unset($data['payment_option']);return $data;
    }
    public static function defaults($user) {
        $data=array_fill_keys(['name','email','phone','address','city'],'');
        $data['whatsapp_same']='0';$data['whatsapp_phone']='';
        if (!$user) return $data;
        foreach (['name','email','phone','address','city'] as $key) $data[$key]=$user->$key ?: '';
        $last=\App\Models\Order::where('user_id',$user->id)->latest('id')->value('shipping_address');
        $contact=json_decode($last ?? '',true) ?: [];
        $data['whatsapp_same']=($contact['whatsapp_same'] ?? '0')==='1' ? '1':'0';
        $data['whatsapp_phone']=$contact['whatsapp_phone'] ?? '';
        $address=$user->addresses()->orderByDesc('set_default')->orderByDesc('id')->first();
        if ($address) foreach (['address','phone'] as $key) if (!$data[$key]) $data[$key]=$address->$key ?: '';
        if (!array_key_exists($data['city'],self::FEES)) $data['city']='';
        return $data;
    }
    public static function base($carts) {
        $subtotal=0; $tax=0; $discount=0;
        foreach($carts as $cart) { $subtotal+=$cart->price*$cart->quantity+\App\Services\ProductAddons::quote($cart->product_id,json_decode($cart->addon_ids?:'[]',true)?:[],$cart->quantity,false)['total']; $tax+=$cart->tax*$cart->quantity; $discount+=$cart->discount; }
        if (!$carts->count() || !$carts->first()->coupon_code) $discount=0;
        return ['subtotal'=>$subtotal,'tax'=>$tax,'discount'=>$discount,'base'=>$subtotal+$tax-$discount];
    }
    public static function phone($phone) {
        $phone=strtr($phone,['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9']);
        $phone=preg_replace('/[^0-9+]/','',$phone);
        return preg_replace('/^(?:\+88|0088|88)(01)/','$1',$phone);
    }
    public static function shipping($data) {
        return array_merge($data,['country'=>'Bangladesh','state'=>'','postal_code'=>'']);
    }
    public static function saveProfile($user,$data) {
        foreach (['name','phone','address','city'] as $key) $user->$key=$data[$key];
        $user->save();
    }
}
