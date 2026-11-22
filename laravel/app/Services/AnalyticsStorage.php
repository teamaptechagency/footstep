<?php
namespace App\Services;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
class AnalyticsStorage {
    const TABLE='storefront_analytics_views';
    public static function ready(){
        try{return Cache::remember('storefront_analytics_ready_v1',60,function(){return Schema::hasTable(self::TABLE);});}catch(\Throwable $e){return false;}
    }
    public static function install(){
        if(!Schema::hasTable(self::TABLE))Schema::create(self::TABLE,function(Blueprint $t){
            $t->bigIncrements('id');$t->char('view_token',32)->unique();$t->char('visitor_hash',64);$t->char('path_hash',64);$t->string('path',512);$t->string('title',191);$t->unsignedBigInteger('product_id')->nullable();$t->string('device',12);$t->string('referrer',191)->default('direct');$t->boolean('customer')->default(false);$t->unsignedInteger('active_seconds')->default(0);$t->dateTime('created_at');$t->dateTime('updated_at');
            $t->index(['created_at','visitor_hash'],'analytics_date_visitor');$t->index(['product_id','created_at'],'analytics_product_date');$t->index(['path_hash','created_at'],'analytics_path_date');
        });
        CheckoutDrafts::install();
        Cache::forget('storefront_analytics_ready_v1');
    }
}
