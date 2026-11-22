<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash,Validator};
use App\Services\CacheManagement as M;
class CacheManagementController extends Controller {
 private function admin(){if(app()->bound('debugbar'))app('debugbar')->disable();abort_unless(auth()->check()&&auth()->user()->user_type==='admin',403);}
 public function index(){$this->admin();$s=M::settings();return response()->view('backend.platform.cache_management',['zone'=>$s['zone_id']??'','configured'=>!empty($s['token'])&&!empty($s['zone_id']),'driver'=>config('cache.default')])->header('Cache-Control','private, no-store');}
 public function server(Request $r){$this->admin();$d=$r->validate(['types'=>'required|array|min:1|max:4','types.*'=>'required|in:application,views,config,routes']);$results=M::server(array_values(array_unique($d['types'])));return response()->json(['results'=>$results])->header('Cache-Control','no-store');}
 public function device(){$this->admin();return response()->json(['requested'=>true,'message'=>'Browser HTTP cache clear requested; support varies. Cookies and login were not requested for deletion.'])->header('Clear-Site-Data','"cache"')->header('Cache-Control','no-store');}
 public function network(Request $r){$this->admin();$d=$r->validate(['scope'=>'required|in:all,urls','urls'=>'nullable|string|max:8000']);$urls=null;if($d['scope']==='urls'){$urls=array_values(array_unique(array_filter(array_map('trim',preg_split('/[\r\n]+/',$d['urls']??'')))));abort_unless(count($urls)>0&&count($urls)<=30&&M::validateUrls($urls),422,'Enter 1–30 URLs belonging to this website.');}$result=M::purge($urls);return response()->json($result,$result['ok']?200:422)->header('Cache-Control','no-store');}
 public function settings(Request $r){$this->admin();$v=Validator::make($r->all(),['zone_id'=>['nullable','regex:/^[a-fA-F0-9]{32}$/D'],'cdn_token'=>['nullable','string','max:512','regex:/^[A-Za-z0-9_\-]+$/D'],'remove'=>'nullable|boolean']);if($v->fails())return back()->withErrors($v);if(!Hash::check((string)$r->input('password'),auth()->user()->password))return back()->withErrors(['password'=>'Current admin password is required.']);M::save($r->input('zone_id'),$r->input('cdn_token'),$r->boolean('remove'));return back()->with('cache_notice','Cloudflare cache settings saved. The token remains encrypted and is not displayed.');}
}
