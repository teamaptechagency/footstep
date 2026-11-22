<?php
namespace App\Http\Controllers;
class NotificationOpenController extends Controller {
 public function open($id){abort_unless(auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);$n=auth()->user()->notifications()->where('id',$id)->firstOrFail();$data=$n->data;$order=$data['order_id']??null;$n->markAsRead();if(!is_numeric($order)||!\App\Models\Order::whereKey((int)$order)->exists())return redirect()->route('admin.all-notification')->with('info','এই অর্ডারটি আর নেই। নোটিফিকেশনটি read করা হয়েছে।');return redirect()->route('all_orders.show',encrypt((int)$order));}
}
