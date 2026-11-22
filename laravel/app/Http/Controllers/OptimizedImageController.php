<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class OptimizedImageController extends Controller {
    public function show(Request $request,$id,$key,$width){
        abort_unless(ctype_digit((string)$id)&&preg_match('/^[a-f0-9]{32}$/D',$key)&&in_array((int)$width,\App\Services\OptimizedImages::WIDTHS,true),404);
        $path=\App\Services\OptimizedImages::resolve((int)$id,$key,(int)$width);
        if(!$path){$upload=\App\Models\Upload::findOrFail($id);return redirect(my_asset($upload->file_name));}
        return response()->file(public_path($path),['Content-Type'=>substr($path,-5)==='.webp'?'image/webp':'image/jpeg','Cache-Control'=>'public, max-age=31536000, immutable','X-Content-Type-Options'=>'nosniff']);
    }
}
