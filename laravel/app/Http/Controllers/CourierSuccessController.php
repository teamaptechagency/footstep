<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\{CourierSuccess,StoreOperations};
class CourierSuccessController extends Controller {
 private function admin(){abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);}
 public function settings(){ $this->admin();$s=CourierSuccess::settings();return response()->view('backend.platform.courier_success',['enabled'=>(bool)$s['enabled'],'configured'=>!empty($s['api_key'])])->header('Cache-Control','private, no-store');}
 public function save(Request $r){$this->admin();$validator=\Illuminate\Support\Facades\Validator::make($r->all(),['api_key'=>'nullable|string|max:500','password'=>'required|string','enabled'=>'nullable|boolean','clear_key'=>'nullable|boolean']);if($validator->fails())return back()->withErrors($validator->errors());$d=$validator->validated();if(!Hash::check($d['password'],auth()->user()->password))return back()->withErrors(['password'=>'Current admin password সঠিক নয়।']);$s=CourierSuccess::settings();if($r->boolean('clear_key'))$s['api_key']='';elseif($r->filled('api_key'))$s['api_key']=$d['api_key'];$s['enabled']=$r->boolean('enabled');StoreOperations::put('bdcourier_success',$s);return back()->with('success','BD Courier settings saved securely.');}
 public function check(Request $r,$id){$this->admin();$order=\App\Models\Order::findOrFail($id);return response()->json(CourierSuccess::lookup($order))->header('Cache-Control','private, no-store');}
}
