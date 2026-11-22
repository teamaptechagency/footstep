<?php
namespace App\Services;
use Illuminate\Http\Request;
class ProductOrigin
{
    const COUNTRIES=['Vietnam','Indonesia','China'];
    public static function value($country){return in_array($country,self::COUNTRIES,true)?$country:'Vietnam';}
    public static function validate(Request $request){return $request->validate(['made_in'=>'sometimes|required|string|in:Vietnam,Indonesia,China']);}
}
