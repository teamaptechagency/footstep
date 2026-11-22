<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class OrderFilters {
    const STATUSES=['pending','confirmed','picked_up','on_the_way','on_delivery','delivered','cancelled'];
    public static function apply($query,$request) {
        $delivery=trim((string)$request->input('delivery_status',''));
        $payment=trim((string)$request->input('payment_status',$request->input('payment_type','')));
        if ($delivery!=='') {
            if(!in_array($delivery,self::STATUSES,true))throw ValidationException::withMessages(['delivery_status'=>'Select a valid delivery status.']);
            $query->where('delivery_status',$delivery);
        }
        if ($payment!=='') {
            if(!in_array($payment,['paid','unpaid'],true))throw ValidationException::withMessages(['payment_status'=>'Select a valid payment status.']);
            $query->where('payment_status',$payment);
        }
        $search=trim((string)$request->input('search',''));
        if ($search!=='') {
            $query->where(function($q)use($search){
                if(preg_match('/^(\d+)\s*-\s*((?:\+?88)?01[3-9]\d{8})$/',$search,$parts)) {
                    $q->where('code','like','%-'.$parts[1])->whereRaw('('.ReturningCustomers::phoneExpression().') = ?',[OnePageCheckout::phone($parts[2])]);
                } else {
                    $normalized=OnePageCheckout::phone($search);
                    if(preg_match('/^01[3-9][0-9]{8}$/D',$normalized))$q->orWhereRaw('('.ReturningCustomers::phoneExpression().') = ?',[$normalized]);
                    $q->orWhere('code','like','%'.$search.'%')->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.phone')) LIKE ?",['%'.$search.'%'])->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(shipping_address, '$.name')) LIKE ?",['%'.$search.'%']);
                }
            });
        }
        $date=trim((string)$request->input('date',''));
        if($date!=='') {
            $parts=preg_split('/\s+to\s+/i',$date);
            if(count($parts)===1)$parts[]=$parts[0];
            if(count($parts)!==2)throw ValidationException::withMessages(['date'=>'Use DD-MM-YYYY to DD-MM-YYYY.']);
            $start=self::day($parts[0]);$end=self::day($parts[1]);
            if(!$start||!$end||$start>$end)throw ValidationException::withMessages(['date'=>'Select a valid date range.']);
            // End is exclusive of the next day, so all orders on the selected end date are included.
            OrderTime::constrain($query,\Carbon\Carbon::parse($start->format('Y-m-d'),'Asia/Dhaka'),\Carbon\Carbon::parse($end->modify('+1 day')->format('Y-m-d'),'Asia/Dhaka')); 
        }
        return $query;
    }
    private static function day($value) {
        $value=trim($value);
        foreach(['d-m-Y','Y-m-d'] as $format) {
            $day=\DateTimeImmutable::createFromFormat('!'.$format,$value);
            if($day&&$day->format($format)===$value)return $day;
        }
        return null;
    }
}
