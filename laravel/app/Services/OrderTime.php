<?php
namespace App\Services;
use Carbon\Carbon;
class OrderTime {
    // Orders.date is the actual placement epoch; legacy created_at may be UTC.
    public static function constrain($q,$start,$end,$prefix='') {
        return $q->where(function($q)use($start,$end,$prefix){
            $q->where(function($q)use($start,$end,$prefix){$q->where($prefix.'date','>',0)->where($prefix.'date','>=',$start->timestamp)->where($prefix.'date','<',$end->timestamp);})
              ->orWhere(function($q)use($start,$end,$prefix){$q->where(function($q)use($prefix){$q->whereNull($prefix.'date')->orWhere($prefix.'date','<=',0);})->where($prefix.'created_at','>=',$start->copy()->utc())->where($prefix.'created_at','<',$end->copy()->utc());});
        });
    }
    public static function localSql($prefix='') {
        return "DATE_ADD(COALESCE(DATE_ADD('1970-01-01 00:00:00', INTERVAL NULLIF(".$prefix."date,0) SECOND),".$prefix."created_at), INTERVAL 6 HOUR)";
    }
    public static function format($value,$format='d M Y, g:i A'){
        if(!$value)return '—';
        try{$date=is_numeric($value)?Carbon::createFromTimestamp((int)$value,'UTC'):($value instanceof \DateTimeInterface?Carbon::instance($value)->copy():Carbon::parse($value,'UTC'));return $date->timezone('Asia/Dhaka')->format($format);}catch(\Throwable $e){return '—';}
    }
    public static function placed($order,$format='d M Y, g:i A'){return self::format($order->date?:$order->created_at,$format);}
    public static function hour($hour){return Carbon::create(2000,1,1,(int)$hour,0,0,'Asia/Dhaka')->format('g A');}
    public static function range($hour){$date=Carbon::create(2000,1,1,(int)$hour,0,0,'Asia/Dhaka');return $date->format('g:i A').' – '.$date->minute(59)->format('g:i A');}
    public static function pie($hours){
        $total=array_sum(array_column($hours,'orders'));$angle=-90;$segments=[];
        foreach($hours as $hour){if(!$hour['orders']||!$total)continue;$ratio=$hour['orders']/$total;$next=$angle+$ratio*360;$x1=120+100*cos(deg2rad($angle));$y1=120+100*sin(deg2rad($angle));$x2=120+100*cos(deg2rad($next));$y2=120+100*sin(deg2rad($next));
            $segments[]=['hour'=>$hour['hour'],'orders'=>$hour['orders'],'percent'=>round($ratio*100,1),'whole'=>$ratio>=1,'path'=>sprintf('M 120 120 L %.4F %.4F A 100 100 0 %d 1 %.4F %.4F Z',$x1,$y1,$ratio>0.5?1:0,$x2,$y2),'color'=>'hsl('.((155+$hour['hour']*31)%360).',45%,43%)'];$angle=$next;
        }return $segments;
    }
}
