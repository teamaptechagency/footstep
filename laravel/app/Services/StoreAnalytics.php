<?php

namespace App\Services;

use Carbon\Carbon;

use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Cache;

use App\Models\Order;

use App\Models\Product;

class StoreAnalytics {

    public static function window($period){

        $now=Carbon::now('Asia/Dhaka');$start=$now->copy();

        if($period==='yesterday')$start->subDay();elseif($period==='week')$start->subDays(6);elseif($period==='month')$start->startOfMonth();elseif($period==='year')$start->startOfYear();else{$period='day';$start->startOfDay();}

        $start->startOfDay();$end=$period==='year'?$start->copy()->addYear():($period==='month'?$start->copy()->addMonth():($period==='week'?$start->copy()->addDays(7):$start->copy()->addDay()));

        return ['period'=>$period,'local_start'=>$start,'local_end'=>$end,'start'=>$start->copy()->utc(),'end'=>$end->copy()->utc(),'label'=>['day'=>'Today','yesterday'=>'Yesterday','week'=>'Last 7 days','month'=>'Month','year'=>'This year'][$period]];

    }

    public static function duration($seconds){$seconds=(int)round($seconds);return $seconds>=60?floor($seconds/60).'m '.($seconds%60).'s':$seconds.'s';}

    public static function report($period='day',$fresh=false){

        $window=self::window($period);$key='store_analytics_report_v6:'.$window['period'].':'.$window['local_start']->format('Y-m-d').':'.(AnalyticsStorage::ready()?'ready':'off');

        if($fresh)return self::build($window);

        return Cache::remember($key,60,function()use($window){return self::build($window);});

    }

    private static function build($window){

        $start=$window['start'];$end=$window['end'];$ready=AnalyticsStorage::ready();

        $result=['window'=>$window,'ready'=>$ready,'generated'=>Carbon::now('Asia/Dhaka'),'traffic'=>['views'=>0,'visitors'=>0,'active'=>0,'average'=>0,'customer_visitors'=>0,'engaged'=>0],'pages'=>collect(),'products'=>collect(),'dwell'=>collect(),'devices'=>collect(),'sources'=>collect()];

        $orders=OrderTime::constrain(Order::query(),$window['local_start'],$window['local_end']);

        $summary=(clone $orders)->selectRaw("COUNT(*) AS total, COALESCE(SUM(CASE WHEN delivery_status <> 'cancelled' THEN grand_total ELSE 0 END),0) AS order_value, COALESCE(SUM(CASE WHEN delivery_status <> 'cancelled' AND payment_status='paid' THEN grand_total ELSE 0 END),0) AS paid_value")->first();

        $result['orders']=['count'=>(int)$summary->total,'value'=>(float)$summary->order_value,'paid'=>(float)$summary->paid_value];

        $result['statuses']=(clone $orders)->selectRaw('delivery_status AS status, COUNT(*) AS total')->groupBy('delivery_status')->get();

        $result['latest']=(clone $orders)->with('user')->latest('id')->limit(5)->get();

        $hourRows=(clone $orders)->selectRaw('HOUR('.OrderTime::localSql().') AS hour, COUNT(*) AS total')->groupBy('hour')->get()->keyBy('hour');

        $hours=[];for($hour=0;$hour<24;$hour++)$hours[]=['hour'=>$hour,'orders'=>(int)($hourRows->get($hour)->total??0)];

        $weekRows=(clone $orders)->selectRaw('WEEKDAY('.OrderTime::localSql().') AS weekday, COUNT(*) AS total')->groupBy('weekday')->get()->keyBy('weekday');

        $weekdays=[];foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day=>$label)$weekdays[]=['day'=>$label,'orders'=>(int)($weekRows->get($day)->total??0)];

        $result['timing']=['hours'=>$hours,'weekdays'=>$weekdays,'peak_hour'=>collect($hours)->sortByDesc('orders')->first(),'peak_day'=>collect($weekdays)->sortByDesc('orders')->first()];

        $result['order_times']=(clone $orders)->latest('created_at')->limit(12)->get(['id','code','date','created_at','grand_total','delivery_status']);



        $result['top_selling']=OrderTime::constrain(DB::table('order_details AS d')->join('orders AS o','o.id','=','d.order_id'),$window['local_start'],$window['local_end'],'o.')->where('o.delivery_status','!=','cancelled')->selectRaw('d.product_id, SUM(d.quantity) AS units, SUM(d.price*d.quantity) AS value')->groupBy('d.product_id')->orderByDesc('units')->limit(8)->get();

        $result['catalog']=['products'=>Product::where('published',1)->count(),'customers'=>\App\Models\User::where('user_type','customer')->count()];

        $grouped=collect();

        if($ready){

            $views=DB::table(AnalyticsStorage::TABLE)->where('created_at','>=',$start)->where('created_at','<',$end);

            $totals=(clone $views)->selectRaw('COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors, COALESCE(SUM(active_seconds),0) AS active, COALESCE(AVG(active_seconds),0) AS average, COUNT(DISTINCT CASE WHEN customer=1 THEN visitor_hash END) AS customer_visitors, SUM(CASE WHEN active_seconds>=10 THEN 1 ELSE 0 END) AS engaged')->first();

            foreach($result['traffic'] as $key=>$value)$result['traffic'][$key]=in_array($key,['average'],true)?(float)$totals->$key:(int)$totals->$key;

            $result['pages']=(clone $views)->selectRaw('path_hash, MAX(path) AS path, MAX(title) AS title, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors, AVG(active_seconds) AS average')->groupBy('path_hash')->orderByDesc('views')->limit(10)->get();

            $productQuery=(clone $views)->whereNotNull('product_id');

            $result['products']=(clone $productQuery)->selectRaw('product_id, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors, SUM(active_seconds) AS active, AVG(active_seconds) AS average')->groupBy('product_id')->orderByDesc('views')->limit(8)->get();

            $result['dwell']=(clone $productQuery)->selectRaw('product_id, COUNT(*) AS views, AVG(active_seconds) AS average, SUM(active_seconds) AS active')->groupBy('product_id')->orderByDesc('average')->limit(8)->get();

            $result['devices']=(clone $views)->selectRaw('device, COUNT(*) AS views')->groupBy('device')->orderByDesc('views')->get();

            $result['sources']=(clone $views)->selectRaw('referrer, COUNT(*) AS views')->groupBy('referrer')->orderByDesc('views')->limit(6)->get();

            $format=in_array($window['period'],['day','yesterday'],true)?'%Y-%m-%d %H:00':($window['period']==='year'?'%Y-%m':'%Y-%m-%d');

            $grouped=(clone $views)->selectRaw("DATE_FORMAT(DATE_ADD(created_at, INTERVAL 6 HOUR), ?) AS bucket, COUNT(*) AS views, COUNT(DISTINCT visitor_hash) AS visitors",[$format])->groupBy('bucket')->get()->keyBy('bucket');

        }

        $series=[];$cursor=$window['local_start']->copy();

        while($cursor<$window['local_end']){

            $key=$cursor->format(in_array($window['period'],['day','yesterday'],true)?'Y-m-d H:00':($window['period']==='year'?'Y-m':'Y-m-d'));

            $row=$grouped->get($key);$series[]=['label'=>$cursor->format(in_array($window['period'],['day','yesterday'],true)?'H:i':($window['period']==='year'?'M':'d M')),'views'=>$row?(int)$row->views:0,'visitors'=>$row?(int)$row->visitors:0];

            if(in_array($window['period'],['day','yesterday'],true))$cursor->addHour();elseif($window['period']==='year')$cursor->addMonth();else $cursor->addDay();

        }

        $result['series']=$series;$ids=collect($result['top_selling'])->pluck('product_id')->merge($result['products']->pluck('product_id'))->merge($result['dwell']->pluck('product_id'))->unique();

        $result['product_map']=Product::whereIn('id',$ids)->get(['id','name','slug','thumbnail_img'])->keyBy('id');return $result;

    }

}

