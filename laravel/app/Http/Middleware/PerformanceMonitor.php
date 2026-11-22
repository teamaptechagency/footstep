<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\PerformanceLog;
class PerformanceMonitor
{
    private static $listening = false;
    public function handle($request, Closure $next)
    {
        // Logs never contain a raw URL, SQL text/bindings, body, headers, user ID or exception message.
        if ($request->is('admin/performance-logs*')) {
            return $next($request);
        }
        try {
            $settings = PerformanceLog::settings();
        } catch (\Throwable $e) {
            return $next($request);
        }
        if (!$settings['enabled']) {
            return $next($request);
        }
        $stats = (object) ['count' => 0, 'sql_ms' => 0.0, 'max_query_ms' => 0.0, 'start' => microtime(true), 'phases' => [], 'intervals' => []];
        $request->attributes->set('_performance_stats', $stats);
        if (!self::$listening) {
            \Illuminate\Support\Facades\DB::listen(function ($event) {
                if (!app()->bound('request')) {
                    return;
                }
                $s = app('request')->attributes->get('_performance_stats');
                if (!$s) {
                    return;
                }
                $end = microtime(true);
                $s->intervals[] = [max($s->start, $end - (float) $event->time / 1000), $end];
                $s->count++;
                $s->sql_ms += (float) $event->time;
                $s->max_query_ms = max($s->max_query_ms, (float) $event->time);
            });
            self::$listening = true;
        }
        $start = $stats->start;
        $boot = defined('LARAVEL_START') ? round(max(0, ($start - LARAVEL_START) * 1000), 1) : null;
        $response = null;
        $error = null;
        $id = bin2hex(random_bytes(8));
        try {
            $response = $next($request);
            $response->headers->set('X-Request-ID', $id);
            return $response;
        } catch (\Throwable $e) {
            $error = $e;
            throw $e;
        } finally {
            $ms = round((microtime(true) - $start) * 1000, 1);
            $status = $response ? $response->getStatusCode() : ($error instanceof \Illuminate\Validation\ValidationException ? 422 : ($error instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface ? $error->getStatusCode() : 500));
            if ($response) {
                $response->headers->set('Server-Timing', 'app;dur=' . $ms . ', db;dur=' . round($stats->sql_ms, 1) . ($boot !== null ? ', boot;dur=' . $boot : ''));
            }
            if ($ms + (float) $boot >= $settings['threshold'] || $status >= 400) {
                $route = $request->route();
                $uri = is_object($route) ? $route->uri() : 'unmatched';
                $product = null;
                if (is_object($route) && $route->getName() === 'product') {
                    $value = $route->parameter('slug');
                    if (is_string($value) && preg_match('/^[A-Za-z0-9_-]{1,240}$/D', $value)) {
                        $product = $value;
                    }
                }
                $detail = PerformanceLog::breakdown($stats, microtime(true));
                try {
                    PerformanceLog::append(['boot_ms' => $boot, 'application_ms' => $boot === null ? null : round($ms + $boot, 1), 'product' => $product, 'phases' => $detail['phases'], 'unmeasured_ms' => $detail['unmeasured_ms'], 'at' => gmdate('c'), 'request_id' => $id, 'method' => $request->method(), 'route' => is_object($route) ? $route->getName() : null, 'pattern' => $uri, 'status' => $status, 'duration_ms' => $ms, 'sql_ms' => round($stats->sql_ms, 1), 'queries' => $stats->count, 'max_query_ms' => round($stats->max_query_ms, 1), 'non_sql_ms' => round(max(0, $ms - $stats->sql_ms), 1), 'peak_memory_mb' => round(memory_get_peak_usage(true) / 1048576, 1), 'error_type' => $error ? get_class($error) : null]);
                } catch (\Throwable $ignored) {
                }
            }
            $request->attributes->remove('_performance_stats');
        }
    }
}
