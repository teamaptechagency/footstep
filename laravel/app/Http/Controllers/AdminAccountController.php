<?php
namespace App\Http\Controllers;
use App\Models\User;
use App\Services\PrimaryAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class AdminAccountController extends Controller {
    private function authorizeAdmin(){abort_unless(auth()->check()&&auth()->user()->user_type==='admin',403);}
    private function target($id){$user=User::where('user_type','admin')->findOrFail($id);PrimaryAdmin::protect($user);return $user;}
    private function confirm(Request $request){$request->validate(['current_password'=>'required|string|max:200']);if(!Hash::check($request->input('current_password'),auth()->user()->password))throw ValidationException::withMessages(['current_password'=>'Your admin password does not match. / আপনার অ্যাডমিন পাসওয়ার্ড সঠিক নয়।']);}
    public function create(){ $this->authorizeAdmin();PrimaryAdmin::id();return view('backend.staff.staffs.admin_form',['account'=>null]); }
    public function edit($id){$this->authorizeAdmin();return view('backend.staff.staffs.admin_form',['account'=>$this->target($id)]);}
    private function fields(Request $request,$user=null){return $request->validate(['name'=>'required|string|max:191','email'=>['required','email','max:191',Rule::unique('users','email')->ignore($user?$user->id:null)],'phone'=>'nullable|string|max:20','password'=>($user?'nullable':'required').'|string|min:8|max:200|confirmed']);}
    public function store(Request $request){$this->authorizeAdmin();PrimaryAdmin::id();$this->confirm($request);$data=$this->fields($request);$user=new User();$user->name=$data['name'];$user->email=$data['email'];$user->phone=$data['phone']??null;$user->user_type='admin';$user->password=Hash::make($data['password']);$user->email_verified_at=now();$user->save();return redirect()->route('staffs.admins')->with('admin_success','Admin added / অ্যাডমিন যোগ হয়েছে।');}
    public function update(Request $request,$id){$this->authorizeAdmin();$user=$this->target($id);$this->confirm($request);$data=$this->fields($request,$user);DB::transaction(function()use($user,$data){$credentialsChanged=$user->email!==$data['email']||!empty($data['password']);$user->name=$data['name'];$user->email=$data['email'];$user->phone=$data['phone']??null;if(!empty($data['password']))$user->password=Hash::make($data['password']);if($credentialsChanged)$user->remember_token=null;$user->save();if($credentialsChanged)$user->tokens()->delete();});return redirect()->route('staffs.admins')->with('admin_success','Admin updated / অ্যাডমিন আপডেট হয়েছে।');}
    public function destroy(Request $request,$id){$this->authorizeAdmin();$user=$this->target($id);if((int)$user->id===(int)auth()->id())throw ValidationException::withMessages(['admin'=>'You cannot delete your own account. / নিজের অ্যাকাউন্ট মুছে ফেলা যাবে না।']);$this->confirm($request);DB::transaction(function()use($user){$user->tokens()->delete();$user->delete();});return redirect()->route('staffs.admins')->with('admin_success','Admin deleted / অ্যাডমিন মুছে ফেলা হয়েছে।');}
}