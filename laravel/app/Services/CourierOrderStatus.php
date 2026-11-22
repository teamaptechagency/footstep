<?php
namespace App\Services;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

/** Business classification only; raw courier data and payment amounts are not rewritten. */
class CourierOrderStatus
{
    private static $shipments=[];
    public static function transition($provider,$raw)
    {
        if($provider==='steadfast')return ['delivered'=>'delivered','cancelled'=>'cancelled','partial_delivered'=>'partial_delivered'][$raw]??null;
        if($provider!=='carrybee')return null;
        $key=strtolower(trim((string)$raw));$key=preg_replace('/[ _-]+/','-',$key);
        if(in_array($key,['order.partial-delivery','partial-delivery','partially-delivered','partial-delivered'],true))return 'partial_delivered';
        if(in_array($key,['order.delivered','delivered'],true))return 'delivered';
        if(in_array($key,['cancelled','canceled','order-cancelled','order-canceled','pickup-cancelled','pickup-canceled','order.pickup-cancelled','order.returned','returned','order.returned-to-merchant','returned-to-merchant'],true))return 'cancelled';
        return null;
    }
    public static function prime($orders)
    {
        $ids=[];foreach($orders as $o)if($o->delivery_status==='cancelled'&&!array_key_exists((int)$o->id,self::$shipments)){$ids[]=(int)$o->id;self::$shipments[(int)$o->id]=[];}
        if(!$ids)return;
        foreach(DB::table('business_settings')->whereIn('type',array_map(fn($id)=>'ops_shipment_'.$id,$ids))->get(['type','value']) as $row){$id=(int)substr($row->type,13);self::$shipments[$id]=json_decode(Crypt::decryptString($row->value),true)?:[];}
    }
    public static function invalidateReports()
    {
        foreach(['day','yesterday','week','month','year'] as $period){$w=StoreAnalytics::window($period);$suffix=$w['period'].':'.$w['local_start']->format('Y-m-d');\Illuminate\Support\Facades\Cache::forget('order_outcomes_v2:'.$suffix);foreach(['ready','off'] as $mode)\Illuminate\Support\Facades\Cache::forget('store_analytics_report_v6:'.$suffix.':'.$mode);}
    }
    public static function forget($id){unset(self::$shipments[(int)$id]);}
    public static function label($order,$parcel=null)
    {
        if($order->delivery_status!=='cancelled')return ucwords(str_replace('_',' ',(string)$order->delivery_status));
        if($parcel===null){self::prime([$order]);$parcel=self::$shipments[(int)$order->id]??[];}
        return (($parcel['cancellation_type']??null)==='partial')?'D-Cancel':'Cancel';
    }
    public static function courierLabel(array $parcel)
    {
        $type=self::transition($parcel['provider']??'steadfast',$parcel['status']??'');
        if($type==='partial_delivered'||($type==='cancelled'&&($parcel['cancellation_type']??null)==='partial'))return 'D-Cancel';if($type==='cancelled')return 'Cancel';return (string)($parcel['status']??'Not booked');
    }
}
