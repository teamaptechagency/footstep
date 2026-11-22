<?php

namespace App\Providers;

use App\Models\Upload;
use App\Services\ImageVariantManifest;
use App\Services\OptimizedImages;
use App\Services\StorefrontData;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
/** Catalog hydration and image manifests belong to the storefront, not application boot logic. */
class StorefrontServiceProvider extends ServiceProvider
{
    public function boot()
    {
        View::composer('frontend.*', function ($view) {
            StorefrontData::primeView($view);
        });
        Upload::saved(function ($upload) {
            $old = $upload->getRawOriginal('file_name');
            if ($old && $old !== $upload->file_name) {
                ImageVariantManifest::forget($old);
            }
            ImageVariantManifest::forget($upload->file_name);
            if ($upload->type === 'image') {
                try {
                    OptimizedImages::prepareMetadata($upload->file_name);
                } catch (\Throwable $ignored) {
                    /* Original URL is a safe fallback if metadata is unavailable. */
                }
            }
        });
        Upload::deleted(function ($upload) {
            ImageVariantManifest::forget($upload->file_name);
        });
    }
}
