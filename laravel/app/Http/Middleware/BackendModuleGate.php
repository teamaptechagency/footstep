<?php

namespace App\Http\Middleware;

use App\Services\BackendModules;
use Closure;
use Illuminate\Support\Str;

class BackendModuleGate
{
    public function handle($request, Closure $next)
    {
        $route = $request->route();
        $admin = $request->user() && in_array($request->user()->user_type, ['admin', 'staff'], true);
        $backend = $request->is('admin', 'admin/*') || ($route && in_array('admin', $route->middleware(), true));
        // Some legacy admin tools live outside /admin; public storefront routes stay independent.
        if (!$backend && $admin && $route) {
            $backend = in_array('auth', $route->middleware(), true) && count(BackendModules::keysForRoute($route)) > 0;
        }
        if (!$backend) {
            return $next($request);
        }
        if (!BackendModules::routeAllowed($route)) {
            if ($route && $route->getName() === 'admin.dashboard' && $admin && $request->user()->user_type === 'admin' && $request->isMethod('GET')) {
                return redirect()->route('admin.modules.index');
            }
            abort(404);
        }
        $response = $next($request);
        if ($admin && $response->getStatusCode() === 200 && !$response instanceof \Symfony\Component\HttpFoundation\StreamedResponse && !$response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse && strpos((string) $response->headers->get('Content-Type'), 'text/html') !== false) {
            $response->setContent(BackendModules::filterHtml($response->getContent()));
        }
        return $response;
    }
}
