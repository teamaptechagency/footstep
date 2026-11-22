<?php
namespace App\Services;

use App\Models\Upload;
use Illuminate\Validation\ValidationException;

class ProductPhotos
{
    public static function prepare($request)
    {
        $request->validate(['photos'=>'nullable|string','thumbnail_img'=>'nullable|string']);
        $gallery = self::ids($request->input('photos'), 'photos', 8);
        $thumbnail = self::ids($request->input('thumbnail_img'), 'thumbnail_img', 1);
        $ids = array_values(array_unique(array_merge($gallery, $thumbnail)));
        $uploads = Upload::whereIn('id', $ids)->get()->keyBy('id');
        $mapped = [];
        foreach ($ids as $id) {
            $upload = $uploads->get($id);
            if (!$upload || $upload->type !== 'image') throw ValidationException::withMessages(['photos'=>'Select valid product images.']);
            $path = realpath(public_path($upload->file_name));
            $root = realpath(public_path('uploads'));
            if (!$path || !$root || strpos(str_replace('\\','/',$path), rtrim(str_replace('\\','/',$root),'/').'/') !== 0) throw ValidationException::withMessages(['photos'=>'Product image is missing from uploads. Restore or upload it again.']);
            $size = @getimagesize($path);
            if (!$size || $size[0]*$size[1]>40000000 || !in_array($size['mime']??'', ['image/jpeg','image/png','image/webp','image/gif','image/bmp','image/x-ms-bmp','image/avif'], true)) throw ValidationException::withMessages(['photos'=>'Select a valid raster product image, maximum 40 megapixels.']);
            // Product publishing only validates selected originals. Resizing is an explicit Image Tools action.
            $mapped[$id] = $id;
        }
        $request->merge([
            'photos'=>implode(',',array_map(function($id)use($mapped){return $mapped[$id];},$gallery)),
            'thumbnail_img'=>$thumbnail?(string)$mapped[$thumbnail[0]]:null,
        ]);
    }

    private static function ids($value, $field, $max)
    {
        if ($value===null || trim($value)==='') return [];
        $ids = explode(',',trim($value));
        if (count($ids)>$max) throw ValidationException::withMessages([$field=>$field==='photos'?'Gallery allows a maximum of 8 images.':'Thumbnail allows only 1 image.']);
        foreach ($ids as $id) if (!preg_match('/^[1-9][0-9]*$/D',trim($id))) throw ValidationException::withMessages([$field=>'Select valid image files.']);
        return array_values(array_unique(array_map('intval',$ids)));
    }

}
