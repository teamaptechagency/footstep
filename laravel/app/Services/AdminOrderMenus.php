<?php
namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\DB;

class AdminOrderMenus
{
    public static function snapshot()
    {
        return ['orders'=>(int)Order::max('id'), 'incomplete'=>now('UTC')->subMinutes(15)->format('Y-m-d H:i:s')];
    }

    public static function mark($menu, $snapshot)
    {
        DB::transaction(function()use($menu,$snapshot){
            DB::table('users')->where('id',auth()->id())->lockForUpdate()->first(['id']);
            $key='admin_menu_'.$menu.'_'.auth()->id();
            $previous=StoreOperations::get($key,['value'=>$menu==='orders'?0:'1970-01-01 00:00:00']);
            if ($snapshot>$previous['value']) StoreOperations::put($key,['value'=>$snapshot]);
        },3);
    }

    public static function counts()
    {
        $order=StoreOperations::get('admin_menu_orders_'.auth()->id(),['value'=>0]);
        $draft=StoreOperations::get('admin_menu_incomplete_'.auth()->id(),['value'=>'1970-01-01 00:00:00']);
        $incomplete=0;
        if (CheckoutDrafts::ready()) {
            $incomplete=DB::table(CheckoutDrafts::TABLE)->whereIn('id',CheckoutDrafts::contactIds())->where('status','open')->where('last_seen','>',$draft['value'])->where('last_seen','<=',now('UTC')->subMinutes(15))->count();
        }
        return ['orders'=>request()->routeIs('all_orders.index')?0:Order::where('id','>',(int)$order['value'])->count(),'incomplete'=>request()->routeIs('admin.incomplete-orders')?0:$incomplete];
    }
}
