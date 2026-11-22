<?php
namespace App\Http\Middleware;
use Closure;
use App\Services\SingleVendor;

class SingleVendorOnly
{
    public function handle($request,Closure $next)
    {
        abort_if(SingleVendor::blocksRoute($request->route()),404);
        $route=$request->route();$action=$route?$route->getActionName():'';
        $user=$request->is('api/*')?$request->user('sanctum'):$request->user();
        abort_if($user&&$user->user_type==='seller'&&substr($action,-7)!=='@logout',403,'Seller access is disabled for this single-vendor store.');
        if(strpos($action,'BusinessSettingsController@')!==false){
            $types=array_merge((array)$request->input('types',[]),(array)$request->input('type',[]));
            array_walk_recursive($types,function($type){abort_if(in_array($type,SingleVendor::DISABLED_SETTINGS,true),422,'This feature is unavailable in single-vendor mode.');});
        }
        return $next($request);
    }
}
