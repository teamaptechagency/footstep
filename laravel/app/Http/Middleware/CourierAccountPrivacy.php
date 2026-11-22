<?php
namespace App\Http\Middleware;
use Closure;
class CourierAccountPrivacy {
 public function handle($request,Closure $next){
  if(app()->bound('debugbar'))app('debugbar')->disable();
  try{$response=$next($request);}catch(\Illuminate\Validation\ValidationException $e){$response=response()->json(['message'=>'Check required fields.','errors'=>$e->errors()],422);}catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){$response=response()->json(['message'=>$e->getStatusCode()===403?'Admin access required.':$e->getMessage()],$e->getStatusCode());}catch(\Throwable $e){$response=response()->json(['message'=>'Courier account request failed. Credentials were not logged.'],500);}
  $response->headers->set('Cache-Control','private, no-store');return $response;
 }
}
