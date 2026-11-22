<?php
namespace App\Services;
use Illuminate\Http\Request;
class ProductComfort
{
    const DEFAULT_NOTE='Super lightweight and Super comfortable';
    public static function value($note){return $note===null?self::DEFAULT_NOTE:trim((string)$note);}
    public static function validate(Request $request){return $request->validate(['comfort_note'=>'sometimes|nullable|string|max:160']);}
    public static function fromRequest(Request $request,$current=null){return $request->has('comfort_note')?trim((string)$request->input('comfort_note')):self::value($current);}
}
