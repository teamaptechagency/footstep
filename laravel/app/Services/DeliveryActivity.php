<?php
namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/** One parcel is counted once per milestone, never once per sync request. */
class DeliveryActivity
{
    const TABLE='courier_parcel_events';

    public static function record($orderId,$kind,array $parcel,$historical=false)
    {
        if(!Schema::hasTable(self::TABLE)||!empty($parcel['mock'])||($parcel['state']??'')!=='booked')return;
        if(!in_array($kind,['booked','delivered','cancelled'],true))return;
        DB::table(self::TABLE)->insertOrIgnore([
            'order_id'=>(int)$orderId,'kind'=>$kind,
            'account_id'=>(string)($parcel['account_id']??'legacy'),
            'event_at'=>$historical?null:Carbon::now('UTC')->format('Y-m-d H:i:s'),
            'observed_at'=>Carbon::now('UTC')->format('Y-m-d H:i:s'),
            'source'=>$historical?'historical_unknown':'live_observation',
        ]);
    }

    public static function report($from=null,$to=null)
    {
        $end=$to?Carbon::createFromFormat('!Y-m-d',$to,'Asia/Dhaka'):Carbon::now('Asia/Dhaka')->startOfDay();
        $start=$from?Carbon::createFromFormat('!Y-m-d',$from,'Asia/Dhaka'):$end->copy()->subDays(13);
        if($end->lt($start)||$start->diffInDays($end)>89)throw new \InvalidArgumentException('Choose up to 90 days, with the start date before the end date.');
        $out=['ready'=>Schema::hasTable(self::TABLE),'from'=>$start->format('Y-m-d'),'to'=>$end->format('Y-m-d'),'days'=>[],
            'totals'=>['booked'=>0,'delivered'=>0,'cancelled'=>0],'current'=>['pending'=>0,'delivered'=>0,'cancelled'=>0],'unknown'=>['booked'=>0,'delivered'=>0,'cancelled'=>0]];
        for($day=$start->copy();$day->lte($end);$day->addDay())$out['days'][$day->format('Y-m-d')]=['booked'=>0,'delivered'=>0,'cancelled'=>0];
        if(!$out['ready'])return $out;
        $events=DB::table(self::TABLE)->where('event_at','>=',$start->copy()->utc()->format('Y-m-d H:i:s'))
            ->where('event_at','<',$end->copy()->addDay()->utc()->format('Y-m-d H:i:s'))->get(['kind','event_at']);
        foreach($events as $event){$day=Carbon::parse($event->event_at,'UTC')->timezone('Asia/Dhaka')->format('Y-m-d');
            if(isset($out['days'][$day][$event->kind])){$out['days'][$day][$event->kind]++;$out['totals'][$event->kind]++;}}
        foreach(DB::table(self::TABLE)->whereNull('event_at')->select('kind')->selectRaw('COUNT(*) AS total')->groupBy('kind')->get() as $row)$out['unknown'][$row->kind]=(int)$row->total;
        $current=DB::table('orders')->whereExists(function($q){$q->selectRaw('1')->from(self::TABLE)->whereColumn(self::TABLE.'.order_id','orders.id')->where(function($q){$q->where('kind','booked')->orWhere(function($q){$q->where('kind','cancelled')->where('source','classification_correction')->where('orders.delivery_status','cancelled');});});})
            ->select('delivery_status')->selectRaw('COUNT(*) AS total')->groupBy('delivery_status')->get();
        foreach($current as $row){$key=in_array($row->delivery_status,['delivered','cancelled'],true)?$row->delivery_status:'pending';$out['current'][$key]+=(int)$row->total;}
        $out['days']=array_reverse($out['days'],true);return $out;
    }
}


