<?php
namespace App\Http\Middleware;
use Closure;
class FootstepCutoverFence
{
    public function handle($request, Closure $next)
    {
        $path = storage_path('framework/footstep-write-fence');
        clearstatcache(true, $path);
        if (is_file($path)) {
            return response('Temporary maintenance. Please try again shortly.', 503)
                ->header('Retry-After', '60')
                ->header('Cache-Control', 'private, no-store, max-age=0');
        }
        return $next($request);
    }
}

