<?php
namespace App\Http\Middleware;

use Closure;
use App\Services\BackupDiagnostics as D;

class WebsiteBackupPrivacy
{
    public function handle($request, Closure $next)
    {
        if (app()->bound('debugbar')) app('debugbar')->disable();
        $id = bin2hex(random_bytes(8));
        $route = $request->route();
        $operation = is_object($route) ? $route->getName() : 'website-backup';
        $post = $request->isMethod('POST');
        if ($post) D::record('request_received', null, $id, $operation);
        try {
            $response = $next($request);
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            $response = $e->getResponse();
        } catch (\Illuminate\Validation\ValidationException $e) {
            $response = response()->json(['message'=>'Input validation failed.', 'errors'=>$e->errors()], 422);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            $response = response()->json(['message'=>'Session শেষ হয়েছে। আবার login করুন।'], 401);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            $status = $e->getStatusCode();
            $response = response()->json(['message'=>$status === 403 ? 'Admin access প্রয়োজন।' : 'Request rejected. Check session and inputs.'], $status);
            foreach ($e->getHeaders() as $name=>$value) {
                if (in_array(strtolower($name), ['retry-after','x-ratelimit-limit','x-ratelimit-remaining','x-ratelimit-reset'], true)) $response->headers->set($name, $value);
            }
        } catch (\Throwable $e) {
            [$code,$message] = D::classify($e);
            D::record($code, 500, $id, $operation, $e);
            $response = response()->json(['message'=>$message, 'error_code'=>$code], 500);
        }
        $status = $response->getStatusCode();
        // A nested Laravel pipeline may already have rendered the throttle exception.
        if ($status === 429) {
            $retry = max(5, min(3600, (int)$response->headers->get('Retry-After', 60)));
            $headers = [];
            foreach (['X-RateLimit-Limit','X-RateLimit-Remaining','X-RateLimit-Reset'] as $name) {
                if ($response->headers->has($name)) $headers[$name] = $response->headers->get($name);
            }
            $response = response()->json(['message'=>'অনুরোধের সীমা হয়েছে। '.$retry.' সেকেন্ড অপেক্ষা করুন। এতে backup worker বন্ধ হয়েছে বোঝায় না।', 'error_code'=>'rate_limited', 'retry_after'=>$retry], 429);
            $response->headers->set('Retry-After', (string)$retry);
            foreach ($headers as $name=>$value) $response->headers->set($name, $value);
        }
        $code = $status === 429 ? 'rate_limited' : ($status>=300 && $status<400 ? 'session_redirect' : ($status===422 ? 'validation_failed' : ($status===403 ? 'admin_access_denied' : ($status===401 ? 'session_expired' : ($status>=400 ? 'request_failed' : 'request_accepted')))));
        $data = [];
        if ($response instanceof \Illuminate\Http\JsonResponse) {
            $data = $response->getData(true);
            if (is_array($data)) {
                $data['request_id'] = $id;
                if ($status>=400 && !isset($data['error_code'])) $data['error_code'] = $code;
                $response->setData($data);
            }
        }
        if ($post || $status>=400) D::record($code, $status, $id, $operation, null, isset($data['errors']) && is_array($data['errors']) ? array_keys($data['errors']) : []);
        $response->headers->set('X-Backup-Request-ID', $id);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'same-origin');
        return $response;
    }
}
