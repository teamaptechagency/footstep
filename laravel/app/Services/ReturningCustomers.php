<?php
namespace App\Services;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
class ReturningCustomers {
    public static function phoneExpression($column='shipping_address'){
        $raw="CASE WHEN JSON_VALID($column) THEN JSON_UNQUOTE(JSON_EXTRACT($column, '$.phone')) ELSE '' END";
        foreach(['০'=>'0','১'=>'1','২'=>'2','৩'=>'3','৪'=>'4','৫'=>'5','৬'=>'6','৭'=>'7','৮'=>'8','৯'=>'9'] as $from=>$to)$raw="REPLACE($raw, '$from', '$to')";
        foreach([' ','+','-','(',')','.'] as $char)$raw="REPLACE($raw, '$char', '')";
        return "CASE WHEN LEFT($raw,4)='0088' THEN SUBSTRING($raw,5) WHEN LEFT($raw,2)='88' THEN SUBSTRING($raw,3) ELSE $raw END";
    }
    public static function phone($order){$address=json_decode($order->shipping_address??'{}',true)?:[];$phone=OnePageCheckout::phone((string)($address['phone']??''));return preg_match('/^01[3-9][0-9]{8}$/D',$phone)?$phone:null;}
    public static function forOrders($orders){
        $orders=collect($orders);$phones=$orders->map(fn($order)=>self::phone($order))->filter()->unique()->values()->all();$result=[];
        if(!$phones)return $result;
        $expression=self::phoneExpression();$history=Order::where('delivery_status','!=','cancelled')->whereRaw("($expression) IN (".implode(',',array_fill(0,count($phones),'?')).")",$phones)->selectRaw("id, combined_order_id, ($expression) AS contact_phone")->orderBy('id')->get()->groupBy('contact_phone');
        foreach($orders as $order){$phone=self::phone($order);$prior=[];
            if($phone&&$order->delivery_status!=='cancelled')foreach($history->get($phone,collect()) as $row){
                if($row->id>=$order->id)continue;
                if($order->combined_order_id&&$row->combined_order_id==$order->combined_order_id)continue;
                $key=$row->combined_order_id?'combined:'.$row->combined_order_id:'order:'.$row->id;$prior[$key]=true;
            }
            $result[$order->id]=['returning'=>count($prior)>0,'previous_orders'=>count($prior),'phone'=>$phone];
        }return $result;
    }
    public static function info($order){return $order?(self::forOrders([$order])[$order->id]??['returning'=>false,'previous_orders'=>0]):['returning'=>false,'previous_orders'=>0];}
    public static function groups(){
        $phone=self::phoneExpression();
        return Order::where('delivery_status','!=','cancelled')->whereRaw("($phone) REGEXP '^01[3-9][0-9]{8}$'")->selectRaw("($phone) AS phone, COUNT(DISTINCT CASE WHEN combined_order_id IS NOT NULL AND combined_order_id>0 THEN CONCAT('c:',combined_order_id) ELSE CONCAT('o:',id) END) AS orders, SUM(grand_total) AS order_value, MAX(id) AS latest_id")->groupBy('phone')->havingRaw('orders>=2');
    }
    public static function listing($search=''){
        $query=DB::query()->fromSub(self::groups(),'r')->join('orders AS latest','latest.id','=','r.latest_id')->select('r.*','latest.shipping_address','latest.created_at','latest.code');
        if($search!=='')$query->where(function($q)use($search){$q->where('r.phone','like','%'.$search.'%')->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(latest.shipping_address, '$.name')) LIKE ?",['%'.$search.'%']);});
        return $query->orderByDesc('r.orders')->orderByDesc('r.latest_id');
    }
}
