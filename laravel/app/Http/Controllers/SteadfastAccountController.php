<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Services\{SteadfastAccounts as Accounts,SteadfastDiagnostics,SteadfastReconcile,StoreOperations};

class SteadfastAccountController extends Controller
{
    private function admin(){abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);}
    private function json($fn){try{return response()->json($fn())->header('Cache-Control','private, no-store');}catch(\Illuminate\Database\QueryException $e){return response()->json(['message'=>'Database unavailable; account action stopped.'],503);}catch(\RuntimeException $e){return response()->json(['message'=>$e->getMessage()],409)->header('Cache-Control','private, no-store');}}
    public function index($account=null){$this->admin();$accounts=Accounts::listing();$settings=$account?Accounts::credentials($account):null;if($settings){unset($settings['api_key'],$settings['secret_key'],$settings['webhook_token']);}return response()->view('backend.platform.steadfast_accounts',compact('accounts','settings','account'))->header('Cache-Control','private, no-store');}
    public function save(Request $r){
        $this->admin();$d=$r->validate(['account'=>['nullable','string','regex:/^(legacy|[a-f0-9]{32})$/D'],'label'=>'required|string|min:1|max:60','mode'=>'required|in:mock,live','active'=>'required|boolean','old_invoice_pattern'=>'required|in:code,fs_id','api_key'=>'nullable|string|max:500','secret_key'=>'nullable|string|max:500','webhook_token'=>'nullable|string|min:32|max:500','password'=>'required|string|max:200']);
        if(!Hash::check($d['password'],auth()->user()->password))return response()->json(['message'=>'Current admin password is incorrect.'],422);
        return $this->json(function()use($d){$id=Accounts::save($d['account']??null,$d);return ['ok'=>true,'url'=>route('steadfast-accounts.show',$id)];});
    }
    public function health($account){$this->admin();return $this->json(function()use($account){return Accounts::using($account,function(){return SteadfastDiagnostics::status();});});}
    public function test($account){$this->admin();return $this->json(function()use($account){return Accounts::using($account,function(){return SteadfastDiagnostics::test();});});}
    public function sync(Request $r,$account){$this->admin();$d=$r->validate(['confirm'=>'required|in:SYNC DELIVERY','restart_old'=>'nullable|boolean']);return $this->json(function()use($account,$d){return Accounts::using($account,function()use($d){$s=StoreOperations::settings();if($s['mode']!=='live'||!$s['active'])throw new \RuntimeException('Enable this live account first.');if(!empty($d['restart_old']))SteadfastReconcile::queue($s['old_invoice_pattern']);return SteadfastDiagnostics::synchronize('manual',2,20);});});}
    public function send(Request $r,$account,$order){$this->admin();$r->validate(['confirm'=>'required|in:SEND PARCEL']);return $this->json(function()use($order,$account){$result=StoreOperations::send($order,$account);$result['message']=$result['ok']?'Sent using '.Accounts::credentials($account)['label'].(!empty($result['mock'])?' (MOCK; no real parcel)':''):$result['message'];return $result;});}
    public function webhook(Request $r,$account){
        return Accounts::using($account,function()use($r,$account){
            $s=StoreOperations::settings();abort_unless($s['mode']==='live'&&!empty($s['webhook_token'])&&hash_equals($s['webhook_token'],(string)$r->bearerToken()),401);
            $d=$r->validate(['invoice'=>'required|string|max:100','consignment_id'=>'required|string|max:100']);
            $parcel=null;$id=null;
            if(preg_match('/^FS-([0-9]+)$/D',$d['invoice'],$m))$id=(int)$m[1];
            else{$matches=\App\Models\Order::where('code',$d['invoice'])->limit(2)->pluck('id');if($matches->count()===1)$id=$matches[0];}
            if($id)$parcel=StoreOperations::shipment($id);
            abort_unless($parcel&&($parcel['account_id']??'legacy')===$account&&!($parcel['mock']??false)&&hash_equals((string)($parcel['invoice']??''),$d['invoice'])&&!empty($parcel['consignment_id'])&&hash_equals((string)$parcel['consignment_id'],$d['consignment_id']),409);
            StoreOperations::sync($id);return response()->json(['ok'=>true])->header('Cache-Control','no-store');
        });
    }
}
