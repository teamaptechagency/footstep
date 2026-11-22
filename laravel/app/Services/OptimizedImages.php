<?php

namespace App\Services;

use App\Models\Upload;
class OptimizedImages
{
    const WIDTHS = [160, 320, 600, 960, 1440];
    const QUALITY = 90;
    public static function prime(array $ids)
    {
        if (!StorefrontData::readOnly()) {
            return;
        }
        $ids = array_values(array_filter($ids, function ($id) {
            return is_int($id) || is_string($id);
        }));
        $request = app('request');
        $missing = [];
        foreach (array_unique($ids) as $id) {
            if (ctype_digit((string) $id) && (int) $id > 0 && !$request->attributes->has('_storefront_value_image-upload-model-' . $id)) {
                $missing[] = $id;
            }
        }
        if (!$missing) {
            return;
        }
        $items = Upload::whereIn('id', $missing)->get()->keyBy('id');
        foreach ($missing as $id) {
            $request->attributes->set('_storefront_value_image-upload-model-' . $id, $items->get($id));
        }
    }
    public static function model($id)
    {
        if (!$id) {
            return null;
        }
        return storefront_request_value('image-upload-model-' . $id, function () use($id) {
            return Upload::find($id);
        });
    }
    private static function source($relative)
    {
        return storefront_request_value('image-source-' . (string) $relative, function () use($relative) {
            return self::sourceFile($relative);
        });
    }
    private static function sourceFile($relative)
    {
        return PerformanceLog::measure('image_metadata', function () use($relative) {
            return self::readSource($relative);
        });
    }
    private static function readSource($relative)
    {
        if (!is_string($relative) || strpos($relative, '..') !== false || !preg_match('/\\.(jpe?g|png|webp|bmp|avif)$/iD', $relative)) {
            return null;
        }
        $path = realpath(public_path($relative));
        $root = realpath(public_path());
        if (!$path || !$root || strpos(str_replace('\\', '/', $path), rtrim(str_replace('\\', '/', $root), '/') . '/') !== 0) {
            return null;
        }
        $size = @getimagesize($path);
        if (!$size || $size[0] * $size[1] > 40000000) {
            return null;
        }
        if (function_exists('exif_read_data') && preg_match('/\\.jpe?g$/iD', $relative)) {
            $exif = @exif_read_data($path, 'IFD0', true);
            $orientation = (int) ($exif['IFD0']['Orientation'] ?? $exif['Orientation'] ?? 0);
            if (in_array($orientation, [5, 6, 7, 8], true)) {
                $size = [$size[1], $size[0]];
            }
        }
        return ['relative' => $relative, 'path' => $path, 'width' => $size[0], 'height' => $size[1], 'key' => substr(hash('sha256', 'responsive-v2-q90:' . $relative . ':' . filemtime($path) . ':' . filesize($path)), 0, 32)];
    }
    private static function format()
    {
        return function_exists('imagewebp') ? 'webp' : 'jpg';
    }
    private static function variant($source, $width)
    {
        $width = min((int) $width, (int) $source['width']);
        return 'uploads/optimized/' . $source['key'] . '-' . $width . '.' . self::format();
    }
    public static function asset($id)
    {
        $u = self::model($id);
        return $u ? self::preparedUrl($u->file_name, 600, false) : null;
    }
    public static function srcset($id)
    {
        $u = self::model($id);
        return $u ? self::preparedSet($u->file_name) : null;
    }
    public static function staticAsset($relative)
    {
        return self::preparedUrl($relative, 960, true);
    }
    public static function staticSet($relative)
    {
        return self::preparedSet($relative);
    }
    private static function preparedSet($relative)
    {
        $m = ImageVariantManifest::read($relative);
        $set = [];
        foreach ($m['variants'] ?? [] as $width => $path) {
            $set[] = my_asset($path) . ' ' . $width . 'w';
        }
        return implode(', ', $set);
    }
    private static function preparedUrl($relative, $preferred, $static)
    {
        $m = ImageVariantManifest::read($relative);
        $variants = $m['variants'] ?? [];
        if ($variants) {
            ksort($variants, SORT_NUMERIC);
            $choice = end($variants);
            foreach ($variants as $width => $path) {
                if ($width >= $preferred) {
                    $choice = $path;
                    break;
                }
            }
            return my_asset($choice);
        }
        return $static ? static_asset($relative) : my_asset($relative);
    }
    public static function prepareMetadata($relative)
    {
        $source = self::sourceFile($relative);
        if (!$source) {
            return false;
        }
        $variants = [];
        // Discover existing old/new outputs without converting any image.
        $legacy = $source;
        $legacy['key'] = substr(hash('sha256', 'responsive-v1:' . $relative . ':' . filemtime($source['path']) . ':' . filesize($source['path'])), 0, 32);
        foreach (self::WIDTHS as $width) {
            $real = min($width, $source['width']);
            foreach ([$source, $legacy] as $candidate) {
                foreach (['webp', 'jpg'] as $format) {
                    $path = 'uploads/optimized/' . $candidate['key'] . '-' . $real . '.' . $format;
                    if (is_file(public_path($path))) {
                        $variants[$real] = $path;
                        break 2;
                    }
                }
            }
            if ($real === $source['width']) {
                break;
            }
        }
        return ImageVariantManifest::write($source, $variants);
    }
    public static function buildUpload($id, $width = null, $mode = 'automatic')
    {
        if ($mode === 'automatic' && !ImageOptimizationPanel::automatic()) {
            return null;
        }
        $upload = Upload::find($id);
        if (!$upload) {
            return null;
        }
        $source = self::sourceFile($upload->file_name);
        if (!$source) {
            return null;
        }
        $result = self::build($source, $width);
        self::prepareMetadata($upload->file_name);
        ImageOptimizationPanel::record($upload, $mode, $result);
        return $result;
    }
    public static function inspect($upload)
    {
        $source = self::sourceFile($upload->file_name);
        if (!$source) {
            return null;
        }
        $variants = [];
        foreach (self::WIDTHS as $width) {
            $relative = self::variant($source, $width);
            if (is_file(public_path($relative))) {
                $size = @getimagesize(public_path($relative));
                $variants[$relative] = ['width' => $size[0] ?? min($width, $source['width']), 'height' => $size[1] ?? null, 'bytes' => filesize(public_path($relative)), 'url' => my_asset($relative)];
            }
            if ($width >= $source['width']) {
                break;
            }
        }
        return ['width' => $source['width'], 'height' => $source['height'], 'source_bytes' => filesize($source['path']), 'variants' => array_values($variants), 'expected' => count(array_unique(array_map(function ($v) use($source) {
            return min($v, $source['width']);
        }, self::WIDTHS))), 'source_key' => $source['key']];
    }
    public static function buildStatic($relative)
    {
        $source = self::sourceFile($relative);
        if (!$source) {
            return null;
        }
        $result = self::build($source);
        self::prepareMetadata($relative);
        return $result;
    }
    public static function resolve($id, $key, $width)
    {
        $upload = Upload::find($id);
        if (!$upload) {
            return null;
        }
        $source = self::source($upload->file_name);
        if (!$source || $source['key'] !== $key) {
            return null;
        }
        $relative = self::variant($source, $width);
        return is_file(public_path($relative)) ? $relative : null;
    }
    private static function build($source, $onlyWidth = null)
    {
        return PerformanceLog::measure('image_processing', function () use($source, $onlyWidth) {
            return \Illuminate\Support\Facades\Cache::lock('image-source-'.hash('sha256',$source['relative']),300)->block(5,function()use($source,$onlyWidth){return is_file($source['path'])?self::buildMeasured($source,$onlyWidth):null;});
        });
    }
    private static function buildMeasured($source, $onlyWidth = null)
    {
        $widths = $onlyWidth ? [$onlyWidth] : self::WIDTHS;
        $image = null;
        $outputs = [];
        try {
            $directory = public_path('uploads/optimized');
            if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
                return null;
            }
            foreach ($widths as $width) {
                $relative = self::variant($source, $width);
                $path = public_path($relative);
                if (!is_file($path)) {
                    if (!$image) {
                        $image = \Image::make($source['path']);
                        if (function_exists('exif_read_data') && strtolower($image->mime()) === 'image/jpeg') {
                            try {
                                $image->orientate();
                            } catch (\Throwable $ignored) {
                            }
                        }
                    }
                    $copy = clone $image;
                    $copy->resize(min($width, $image->width()), null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    if (self::format() === 'jpg') {
                        $canvas = \Image::canvas($copy->width(), $copy->height(), '#ffffff')->insert($copy);
                        $copy->destroy();
                        $copy = $canvas;
                    }
                    $temp = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
                    $copy->save($temp, self::QUALITY, self::format());
                    $copy->destroy();
                    if (!@rename($temp, $path) && is_file($temp)) {
                        @unlink($temp);
                    }
                }
                if (is_file($path)) {
                    @chmod($path, 0644);
                    $outputs[] = $relative;
                }
                if ($width >= $source['width']) {
                    break;
                }
            }
        } catch (\Throwable $e) {
            return null;
        } finally {
            if ($image) {
                $image->destroy();
            }
        }
        return $onlyWidth ? $outputs[0] ?? null : $outputs;
    }
}
