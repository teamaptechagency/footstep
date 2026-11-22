<?php
namespace App\Http\Middleware;

use App\Services\AdminOrderMenus;
use Illuminate\Support\Facades\DB;

class AdminOrderReadState
{
    public function handle($request, \Closure $next)
    {
        $name=$request->route()?$request->route()->getName():'';
        $tracked=in_array($name,['all_orders.index','admin.incomplete-orders','store-ops.invoice','all_orders.show','orders.show'],true);
        $admin=$tracked && auth()->check() && auth()->user()->user_type==='admin' && !auth()->user()->banned;
        $snapshot=$admin?AdminOrderMenus::snapshot():null;
        $response=$next($request);
        if (!$admin || $response->getStatusCode()!==200) return $response;
        if ($name==='all_orders.index') AdminOrderMenus::mark('orders',$snapshot['orders']);
        elseif ($name==='admin.incomplete-orders') AdminOrderMenus::mark('incomplete',$snapshot['incomplete']);
        else {
            $id=$request->route('id');
            if ($name!=='store-ops.invoice') {
                try {$id=decrypt($id);} catch (\Throwable $e) {return $response;}
            }
            if (is_numeric($id)) DB::table('orders')->where('id',(int)$id)->where(function($q){$q->where('viewed',0)->orWhereNull('viewed');})->update(['viewed'=>1]);
        }
        return $response;
    }
}
