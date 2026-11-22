<?php
namespace App\Http\Middleware;
use Closure;
class BlockedCustomerIp {
 public function handle($r,Closure $next){if($r->is('admin','admin/*','login','logout','api/steadfast/*'))return $next($r);$blocked=\Illuminate\Support\Facades\Cache::remember('ops_blocked_ips',60,fn()=>\App\Services\StoreOperations::get('blocked_ips'));if(in_array($r->ip(),$blocked,true))return response('Access blocked. Contact the store administrator.',403)->header('Cache-Control','no-store');\App\Services\OrderProtection::assertAllowed($r);return $next($r);}
}
