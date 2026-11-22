<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
class ReturningCustomerController extends Controller {
    public function index(Request $request){
        $user=auth()->user();$permissions=$user&&$user->user_type==='staff'?json_decode($user->staff->role->permissions??'[]',true):[];abort_unless($user&&($user->user_type==='admin'||in_array('8',$permissions?:[])),403);
        $request->validate(['search'=>'nullable|string|max:100']);$search=trim((string)$request->input('search',''));$normalized=\App\Services\OnePageCheckout::phone($search);if(preg_match('/^01[3-9][0-9]{8}$/D',$normalized))$search=$normalized;
        return view('backend.customer.returning',['customers'=>\App\Services\ReturningCustomers::listing($search)->paginate(20)->appends($request->query()),'search'=>$search]);
    }
}
