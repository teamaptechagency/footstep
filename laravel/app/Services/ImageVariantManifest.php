<?php

namespace App\Services;

/** Prepared by upload/optimization/CLI; storefront reads never decode source images. */
class ImageVariantManifest
{
    private static function file($relative)
    {
        return storage_path('app/image-manifests/' . hash('sha256', (string) $relative) . '.json');
    }
    public static function read($relative)
    {
        return storefront_request_value('image-manifest-' . $relative, function () use($relative) {
            $path = self::file($relative);
            if (!is_file($path)) {
                return null;
            }
            $size = filesize($path);
            if ($size === false || $size > 32768) {
                return null;
            }
            $raw = @file_get_contents($path);
            $data = is_string($raw) ? json_decode($raw, true) : null;
            if (!is_array($data) || ($data['version'] ?? null) !== 1 || ($data['relative'] ?? null) !== $relative || !is_array($data['variants'] ?? null)) {
                return null;
            }
            $valid = [];
            foreach ($data['variants'] as $width => $variant) {
                if (!ctype_digit((string) $width) || (int) $width < 1 || !is_string($variant) || !preg_match('~^uploads/optimized/[a-f0-9]{32}-[0-9]+\\.(webp|jpg)$~D', $variant)) {
                    continue;
                }
                $valid[(int) $width] = $variant;
            }
            $data['variants'] = $valid;
            return $data;
        });
    }
    public static function write($source, array $variants)
    {
        try { return \Illuminate\Support\Facades\Cache::lock('image-source-'.hash('sha256',$source['relative']),300)->block(5,function()use($source,$variants){return is_file($source['path']??public_path($source['relative']))?self::writeUnlocked($source,$variants):false;}); } catch (\Illuminate\Contracts\Cache\LockTimeoutException $e) { return false; }
    }
    private static function writeUnlocked($source, array $variants)
    {
        $path = self::file($source['relative']);
        $directory = dirname($path);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            return false;
        }
        $data = ['version' => 1, 'relative' => $source['relative'], 'width' => $source['width'], 'height' => $source['height'], 'source_key' => $source['key'], 'variants' => $variants];
        $temp = $path . '.' . bin2hex(random_bytes(5)) . '.tmp';
        try {
            if (file_put_contents($temp, json_encode($data, JSON_UNESCAPED_SLASHES), LOCK_EX) === false) {
                return false;
            }
            @chmod($temp, 0644);
            if (!@rename($temp, $path)) {
                return false;
            }
            if (app()->bound('request')) {
                app('request')->attributes->remove('_storefront_value_image-manifest-' . $source['relative']);
            }
            return true;
        } finally {
            if (is_file($temp)) {
                @unlink($temp);
            }
        }
    }
    public static function forget($relative)
    {
        $path = self::file($relative);
        if (is_file($path)) {
            @unlink($path);
        }
        if (app()->bound('request')) {
            app('request')->attributes->remove('_storefront_value_image-manifest-' . $relative);
        }
    }
}
