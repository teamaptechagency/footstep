<?php
namespace App\Http\Middleware;

class RetiredMessaging
{
    public function handle($request,\Closure $next)
    {
        if($request->is('admin/inbox','admin/inbox/*','inbox','inbox/*','api/inbox','api/inbox/*','admin/website-chat','admin/website-chat/*','website-chat','website-chat/*')){
            return response('Messaging has been removed.',410)->header('Cache-Control','no-store');
        }
        if($request->is('admin/activation','admin/activation/*','admin/file_system','admin/file_system/*')){
            return response('This legacy settings page has been retired. Use Settings or Cleaner → Cache Management.',410)->header('Cache-Control','no-store');
        }
        return $next($request);
    }
}
