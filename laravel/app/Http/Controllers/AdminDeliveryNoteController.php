<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\StoreOperations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDeliveryNoteController extends Controller
{
    public function save(Request $request,$order)
    {
        abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);
        $data=$request->validate(['delivery_note'=>'nullable|string|max:254']);
        DB::transaction(function()use($order,$data){
            Order::whereKey($order)->lockForUpdate()->firstOrFail();
            StoreOperations::put('admin_delivery_note_'.(int)$order,['text'=>(string)($data['delivery_note']??'')]);
        },3);
        return redirect()->route('store-ops.invoice',$order)->with('success','Delivery man note saved.');
    }
}
