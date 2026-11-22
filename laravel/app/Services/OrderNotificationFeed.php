<?php
namespace App\Services;

class OrderNotificationFeed
{
    public const GROUPS=[
        'confirmed'=>['label'=>'কনফার্ম','statuses'=>['confirmed']],
        'delivery'=>['label'=>'ডেলিভারি','statuses'=>['delivered','on_delivery','on_the_way','shipped','picked_up','ready_to_pick_up','out_for_delivery']],
        'cancelled'=>['label'=>'ক্যানসেল','statuses'=>['cancelled','canceled','partial_delivered']],
        'other'=>['label'=>'অন্যান্য','statuses'=>[]],
    ];
    public static function category($status){foreach(self::GROUPS as $key=>$g)if(in_array($status,$g['statuses'],true))return $key;return 'other';}
    public static function filter($query,$category){
        $query->where(function($q){$q->where('data->status','!=','placed')->orWhereNull('data->status');});
        if(!isset(self::GROUPS[$category]))return $query;
        if($category!=='other')return $query->whereIn('data->status',self::GROUPS[$category]['statuses']);
        $known=[];foreach(self::GROUPS as $g)$known=array_merge($known,$g['statuses']);
        return $query->where(function($q)use($known){$q->whereNotIn('data->status',$known)->orWhereNull('data->status');});
    }
    public static function counts($user){
        $counts=array_fill_keys(array_keys(self::GROUPS),0);
        $column=\DB::connection()->getQueryGrammar()->wrap('data->status');
        foreach($user->unreadNotifications()->selectRaw($column.' AS event_status, COUNT(*) AS total')->groupByRaw($column)->get() as $row)
            if($row->event_status!=='placed')
            $counts[self::category($row->event_status)]+=(int)$row->total;
        return $counts;
    }
    public static function item($n){
        $status=$n->data['status']??'updated';$group=self::category($status);
        return ['id'=>$n->id,'code'=>$n->data['order_code']??'Order','status'=>$status,'group'=>$group,'label'=>self::GROUPS[$group]['label'],
            'at'=>$n->created_at->timezone('Asia/Dhaka')->format('d M, g:i A'),'url'=>route('admin.notification-open',$n->id)];
    }
}
