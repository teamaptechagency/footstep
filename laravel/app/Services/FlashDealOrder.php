<?php
namespace App\Services;
use Illuminate\Validation\ValidationException;
class FlashDealOrder
{
    public static function prepare($request)
    {
        $data=$request->validate(['products'=>'required|array|min:1|max:1000','products.*'=>'required|integer|distinct|exists:products,id','ordered_products'=>'sometimes|array|max:1000','ordered_products.*'=>'required|integer|distinct']);
        $rules=[];
        foreach($data['products'] as $id){$rules['discount_'.$id]='required|numeric|min:0';$rules['discount_type_'.$id]='required|in:amount,percent';}
        $request->validate($rules);
        foreach($data['products'] as $id)if($request->input('discount_type_'.$id)==='percent'&&(float)$request->input('discount_'.$id)>100)throw ValidationException::withMessages(['products'=>'Percentage discount cannot exceed 100.']);
        $selected=array_map('strval',$data['products']);$ordered=array_map('strval',$data['ordered_products']??$selected);
        if(count($ordered)!==count($selected)||array_diff($ordered,$selected)||array_diff($selected,$ordered))throw ValidationException::withMessages(['ordered_products'=>'Product order must contain each selected product exactly once. Refresh the product list and try again.']);
        $request->merge(['products'=>$ordered]);return $ordered;
    }
}
