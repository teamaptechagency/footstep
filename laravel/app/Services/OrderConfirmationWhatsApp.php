<?php
namespace App\Services;

class OrderConfirmationWhatsApp
{
    public static function phone($value)
    {
        $value=trim((string)$value);
        if(preg_match('~(?:wa\.me/|phone=)([+0-9]+)~',$value,$match))$value=$match[1];
        if(!preg_match('/^[+0-9\s()-]+$/D',$value))return null;
        $value=preg_replace('/[^0-9]/','',$value);
        if(strpos($value,'00')===0)$value=substr($value,2);
        if(preg_match('/^01[3-9][0-9]{8}$/D',$value))$value='88'.$value;
        return preg_match('/^[1-9][0-9]{6,14}$/D',$value)?$value:null;
    }

    public static function message($order)
    {
        return "আমার অর্ডারটা কনফার্ম করুন।\nঅর্ডার নম্বর: ".(string)$order->code;
    }

    public static function expiresAt($order) {
        return ($order->date ? (int)$order->date : \Carbon\Carbon::parse($order->created_at,'UTC')->timestamp)+600;
    }
    public static function link($order)
    {
        if(time()>=self::expiresAt($order))return null;
        $phone=self::phone(get_setting('order_confirmation_whatsapp')?:get_setting('contact_phone'));
        return $phone?'https://wa.me/'.$phone.'?text='.rawurlencode(self::message($order)):null;
    }
}
