<?php
namespace App\Http\Middleware;
use Closure;use Illuminate\Support\Facades\DB;
class NativeCatalogRevision {
 public function handle($request,Closure $next){$handler=app(\Illuminate\Contracts\Debug\ExceptionHandler::class);try{$response=$next($request);}catch(\Throwable $e){$handler->report($e);$response=$handler->render($request,$e);}$revision=DB::table('business_settings')->where('type','mobile_catalog_revision')->value('value');$response->headers->set('X-FootStep-Catalog-Version',(string)($revision??'0'));return $response;}
}
