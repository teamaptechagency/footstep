<?php
namespace App\Http\Middleware;
use Closure;
class InboxPrivacy {
 public function handle($request,Closure $next){
  if(app()->bound('debugbar'))app('debugbar')->disable();
  try{$response=$next($request);}catch(\Illuminate\Validation\ValidationException $e){throw $e;}catch(\Illuminate\Http\Exceptions\HttpResponseException $e){$response=$e->getResponse();}catch(\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e){$response=response('Inbox request rejected. Check inputs, credentials and permissions.',$e->getStatusCode());}catch(\Throwable $e){$response=response('Inbox could not complete this request. No private details were logged.',500);}
  $response->headers->set('Cache-Control','private, no-store');$response->headers->set('X-Content-Type-Options','nosniff');$response->headers->set('Referrer-Policy','same-origin');return $response;
 }
}
