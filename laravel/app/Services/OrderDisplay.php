<?php
namespace App\Services;
use App\Models\BusinessSetting;
use App\Models\Order;
use Illuminate\Support\Facades\Cache;
class OrderDisplay {
    const KEY='order_display_format_v1';
    public static function mode(){return Cache::remember(self::KEY,3600,function(){ $mode=BusinessSetting::where('type',self::KEY)->value('value');return in_array($mode,['full','last_phone'],true)?$mode:'last_phone';});}
    public static function label($order){
        if(!$order)return '—';
        $code=(string)$order->code;if(self::mode()==='full')return $code;
        $suffix=substr($code,strrpos($code,'-')===false?0:strrpos($code,'-')+1);
        $address=json_decode((string)$order->shipping_address,true)?:[];
        $phone=OnePageCheckout::phone((string)($address['phone']??''));
        return $phone!==''?$suffix.' - '.$phone:$suffix;
    }
    public static function find($value){
        if(!is_string($value)||strlen($value)>100)return null;
        $value=trim($value);$exact=Order::where('code',$value)->first();if($exact)return $exact;
        if(self::mode()!=='last_phone'||!preg_match('/^(\d{4,30})\s*-\s*([+0-9\s()]{7,30})$/D',$value,$m))return null;
        $phone=OnePageCheckout::phone($m[2]);$matched=[];
        foreach(Order::where('code','like','%-'.$m[1])->cursor() as $order){$address=json_decode($order->shipping_address,true)?:[];if(OnePageCheckout::phone((string)($address['phone']??''))===$phone)$matched[]=$order;if(count($matched)>1)return null;}
        return count($matched)===1?$matched[0]:null;
    }
}