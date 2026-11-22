<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
/** Small, secret-free background snapshot. Never inspect queue message bodies. */
class RuntimeHealth
{
    public static function file()
    {
        return storage_path('app/runtime-health.json');
    }
    public static function read()
    {
        $path = self::file();
        if (!is_file($path) || !is_readable($path) || filesize($path) > 8192) {
            return null;
        }
        $raw = @file_get_contents($path);
        $data = is_string($raw) ? json_decode($raw, true) : null;
        if (!is_array($data) || !isset($data['at'], $data['php'], $data['queue'], $data['cache']) || !is_string($data['at']) || strtotime($data['at']) === false) {
            return null;
        }
        return $data;
    }
    public static function collect()
    {
        $report = ['at' => gmdate('c'), 'php' => PHP_VERSION, 'queue' => (string) config('queue.default'), 'cache' => (string) config('cache.default'), 'config_cached' => app()->configurationIsCached(), 'pending_jobs' => null, 'failed_jobs' => null, 'database_check' => 'ok'];
        try {
            if (Schema::hasTable('jobs')) {
                $report['pending_jobs'] = DB::table('jobs')->count();
            }
            if (Schema::hasTable('failed_jobs')) {
                $report['failed_jobs'] = DB::table('failed_jobs')->count();
            }
        } catch (\Throwable $ignored) {
            $report['database_check'] = 'unavailable';
        }
        $path = self::file();
        $tmp = $path . '.' . bin2hex(random_bytes(5)) . '.tmp';
        try {
            if (file_put_contents($tmp, json_encode($report), LOCK_EX) === false || !rename($tmp, $path)) {
                throw new \RuntimeException('Runtime health snapshot could not be written.');
            }
            @chmod($path, 0600);
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
        return $report;
    }
}
