<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\{SteadfastDiagnostics,SteadfastReconcile,StoreOperations,MetaPixel,MetaIntegrationHealth};

class IntegrationHealthController extends Controller
{
    private function admin(){abort_unless(auth()->check()&&auth()->user()->user_type==='admin'&&!auth()->user()->banned,403);}
    private function json($data){return response()->json($data)->header('Cache-Control','private, no-store');}
    private function run($callback){try{return $this->json($callback());}catch(\Illuminate\Database\QueryException $e){return $this->json(['message'=>'Saved integration settings could not be read. Check database and hosting logs.'])->setStatusCode(503);}catch(\RuntimeException $e){return $this->json(['message'=>$e->getMessage()])->setStatusCode(409);}catch(\Throwable $e){return $this->json(['message'=>'Integration request could not finish. Check hosting quota/network and refresh status before retrying.'])->setStatusCode(503);}}
    private function metaHealth(){return new MetaIntegrationHealth(app(MetaPixel::class),MetaPixel::settings(),dirname(base_path()).'/footstep-meta-data');}
    public function steadfast(){$this->admin();return $this->run(function(){return SteadfastDiagnostics::status();});}
    public function steadfastTest(){$this->admin();return $this->run(function(){return SteadfastDiagnostics::test();});}
    public function steadfastSync(Request $r){$this->admin();$d=$r->validate(['confirm'=>'required|in:SYNC DELIVERY','restart_old'=>'nullable|boolean']);return $this->run(function()use($d){$s=StoreOperations::settings();if($s['mode']!=='live'||empty($s['api_key'])||empty($s['secret_key']))throw new \RuntimeException('Live mode এবং saved API Key / Secret Key প্রয়োজন।');if(!empty($d['restart_old']))SteadfastReconcile::queue($s['old_invoice_pattern']??'code');return SteadfastDiagnostics::synchronize('manual',2,20);});}
    public function meta(Request $r){$this->admin();$d=$r->validate(['days'=>'nullable|integer|min:1|max:30']);return $this->run(function()use($d){return $this->metaHealth()->summary($d['days']??7);});}
    public function metaTest(Request $r){$this->admin();$r->validate(['confirm'=>'required|in:SEND TEST PAGEVIEW']);return $this->run(function(){return $this->metaHealth()->test(function($payload,$token,$version){$body=['data'=>[$payload['event']],'test_event_code'=>$payload['test_code']];$r=Http::withToken($token)->acceptJson()->withOptions(['allow_redirects'=>false,'connect_timeout'=>3])->timeout(8)->post('https://graph.facebook.com/'.$version.'/'.$payload['pixel'].'/events',$body);$j=$r->json()?:[];return ['status'=>$r->status(),'events_received'=>(int)($j['events_received']??0),'error_code'=>(int)($j['error']['code']??0)];},route('home'));});}
}
