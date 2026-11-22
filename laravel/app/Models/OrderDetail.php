<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    protected static function booted()
    {
        static::creating(function($model){$model->seller_id=\App\Services\SingleVendor::ownerId();$model->product_name_snapshot=$model->product_name_snapshot ?: \App\Models\Product::whereKey($model->product_id)->value('name');});
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function pickup_point()
    {
        return $this->belongsTo(PickupPoint::class);
    }

    public function refund_request()
    {
        return $this->hasOne(RefundRequest::class);
    }

    public function affiliate_log()
    {
        return $this->hasMany(AffiliateLog::class);
    }
}
