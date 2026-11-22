<?php
namespace App\Services;
use App\Models\Upload;
class InvoiceImages {
 public static function source($product,$pdf=false){
  $fallback='data:image/svg+xml;base64,'.base64_encode('<svg xmlns="http://www.w3.org/2000/svg" width="48" height="64"><rect width="48" height="64" fill="#edf1ef"/><text x="24" y="34" text-anchor="middle" font-size="8" fill="#61756b">No image</text></svg>');
  $upload=$product?Upload::find($product->thumbnail_img):null;if(!$upload)return $pdf?self::pdfPlaceholder():$fallback;
  $root=realpath(public_path('uploads'));$path=realpath(public_path($upload->file_name));if(!$path||!$root||!is_file($path)||strpos(str_replace('\\','/',$path),rtrim(str_replace('\\','/',$root),'/').'/')!==0)return $pdf?self::pdfPlaceholder():$fallback;
  $size=@getimagesize($path);if(!$size||!in_array($size['mime']??'',['image/jpeg','image/png','image/webp','image/gif'],true))return $pdf?self::pdfPlaceholder():$fallback;
  if(!$pdf)return OptimizedImages::asset($upload->id)?:my_asset($upload->file_name);
  // PDF uses a small in-memory local image: no remote HTTP request or filesystem conversion.
  if(filesize($path)>20971520||$size[0]*$size[1]>40000000)return self::pdfPlaceholder();
  try{$image=\Image::make($path)->orientate()->resize(120,160,function($constraint){$constraint->aspectRatio();$constraint->upsize();});$bytes=(string)$image->encode('jpg',85);$image->destroy();return 'data:image/jpeg;base64,'.base64_encode($bytes);}catch(\Throwable $e){return self::pdfPlaceholder();}
 }
 private static function pdfPlaceholder(){return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jRZkAAAAASUVORK5CYII=';}
}
