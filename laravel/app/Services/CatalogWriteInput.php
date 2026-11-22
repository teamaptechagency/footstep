<?php
namespace App\Services;

use Illuminate\Validation\ValidationException;

class CatalogWriteInput
{
    public static function language()
    {
        $value=env('DEFAULT_LANGUAGE') ?: config('app.locale','en');
        return is_string($value)&&preg_match('/^[a-z]{2}(?:[-_][A-Za-z]{2})?$/D',$value)?$value:'en';
    }

    public static function prepare($request,$flash=false)
    {
        if(!$request->filled('lang'))$request->merge(['lang'=>self::language()]);
        $request->validate(['lang'=>'required|string|max:10|exists:languages,code']);
        if($flash){
            $request->validate(['title'=>'required|string|max:255','date_range'=>'required|string|max:100']);
        }else{
            $request->validate(['name'=>'required|string|max:255','category_id'=>'required|integer|exists:categories,id','unit_price'=>'required|numeric|min:0','tags'=>'nullable|array','tags.0'=>'nullable|string|max:20000']);
            $raw=$request->input('tags.0');
            $tags=$raw?json_decode($raw):[];
            if(!is_array($tags))throw ValidationException::withMessages(['tags'=>'Tags-এর format সঠিক নয়। Tags আবার বাছাই করুন।']);
            foreach($tags as $tag)if(!is_object($tag)||!isset($tag->value)||!is_string($tag->value))throw ValidationException::withMessages(['tags'=>'Select valid product tags.']);
            $request->merge(['tags'=>[json_encode($tags)]]);
        }
        if($request->filled('date_range')){
            $parts=explode(' to ',$request->input('date_range'));
            if(count($parts)!==2||!strtotime($parts[0])||!strtotime($parts[1])||strtotime($parts[1])<strtotime($parts[0]))throw ValidationException::withMessages(['date_range'=>'শুরুর ও শেষের সঠিক তারিখ বাছাই করুন; শেষ তারিখ আগে হতে পারবে না।']);
        }
    }
}
