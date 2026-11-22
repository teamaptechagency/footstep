<?php
namespace App\Http\Middleware;
use Closure;
use Symfony\Component\HttpFoundation\AcceptHeader;
class StorefrontCompression {
    public function handle($request,Closure $next){
        $response=$next($request);
        if(!$request->isMethod('GET')||$request->is('admin','admin/*','media/*')||$response->getStatusCode()!==200||$response->headers->has('Content-Encoding')||!function_exists('gzencode'))return $response;
        $type=$response->headers->get('Content-Type','');$body=$response->getContent();
        if(strpos($type,'text/html')===false||!is_string($body)||strlen($body)<2048)return $response;
        $response->setVary('Accept-Encoding',false);$accept=AcceptHeader::fromString($request->header('Accept-Encoding',''));
        if(!$accept->has('gzip')||$accept->get('gzip')->getQuality()<=0)return $response;
        $compressed=gzencode($body,6);if($compressed!==false&&strlen($compressed)<strlen($body)){$response->setContent($compressed);$response->headers->set('Content-Encoding','gzip');$response->headers->set('Content-Length',strlen($compressed));}
        return $response;
    }
}
