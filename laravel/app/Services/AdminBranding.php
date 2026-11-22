<?php

namespace App\Services;

class AdminBranding
{
    private static $version;
    public static function version()
    {
        if (self::$version !== null) {
            return self::$version;
        }
        try {
            $manager = new SecureReleaseManager(base_path(), dirname(base_path()) . '/footstep-update-data', config('app.key'));
            $version = $manager->state()['version'] ?? '';
            if (preg_match('/^\\d+\\.\\d+\\.\\d+$/D', $version)) {
                return self::$version = $version;
            }
        } catch (\Throwable $e) {
        }
        // A direct local/cPanel deployment has no signed install state yet.
        return self::$version = '2.6.0';
    }
    public static function publicCredit()
    {
        return (string) get_setting('public_developer_credit_v1', '1') === '1';
    }
}
