<?php
namespace App\Services;

use Intervention\Image\ImageManager;

/** Create a verified derivative; never overwrite the original upload. */
class ProductImageConverter
{
    public static function convert($source, $destination)
    {
        $size = @getimagesize($source);
        if (!$size || $size[0] * $size[1] > 40000000) throw new \RuntimeException('invalid_image');
        $directory = dirname($destination);
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) throw new \RuntimeException('image_directory_unavailable');
        $drivers = extension_loaded('gd') ? ['gd'] : [];
        if (extension_loaded('imagick')) $drivers[] = 'imagick';
        $failure = null;
        foreach ($drivers as $driver) {
            $image = $canvas = null;
            $temporary = $destination . '.' . bin2hex(random_bytes(6)) . '.tmp';
            try {
                $manager = new ImageManager(['driver' => $driver]);
                $image = $manager->make($source);
                // Missing EXIF is not a format error. PNG/WebP do not need JPEG orientation.
                if (($size['mime'] ?? '') === 'image/jpeg' && function_exists('exif_read_data')) {
                    try { $image->orientate(); } catch (\Throwable $ignored) { /* Keep decoded pixels. */ }
                }
                // Crop to 3:4 so storefront images have no padded side bands.
                $image->fit(450, 600, null, 'center');
                $canvas = $manager->canvas(450, 600, '#ffffff')->insert($image, 'center');
                $canvas->save($temporary, 86, 'jpg');
                $output = @getimagesize($temporary);
                if (!$output || $output[0] !== 450 || $output[1] !== 600) throw new \RuntimeException('image_output_invalid');
                @chmod($temporary, 0644);
                if (!@rename($temporary, $destination)) throw new \RuntimeException('image_write_failed');
                return;
            } catch (\Throwable $exception) { $failure = $exception; }
            finally {
                if (is_file($temporary)) @unlink($temporary);
                if ($canvas) $canvas->destroy();
                if ($image) $image->destroy();
            }
        }
        throw new \RuntimeException('image_conversion_unavailable', 0, $failure);
    }
}
