<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\Inbox\{Store,Webhooks};
class InboxWebhookController extends Controller {
 public function handle(Request $r,$uuid){
  $b=DB::table('inbox_businesses')->where('uuid',$uuid)->first();abort_unless($b,404);
  $secrets=Store::unpack($b->secrets);
  if($r->isMethod('get')){
   $mode=$r->query('hub_mode',$r->query('hub.mode'));$token=$r->query('hub_verify_token',$r->query('hub.verify_token'));$challenge=$r->query('hub_challenge',$r->query('hub.challenge'));
   abort_unless($mode==='subscribe'&&is_string($token)&&!empty($secrets['verify_token'])&&hash_equals($secrets['verify_token'],$token)&&is_string($challenge)&&strlen($challenge)<200,403);
   return response($challenge,200,['Content-Type'=>'text/plain','Cache-Control'=>'no-store']);
  }
  abort_if(strlen($r->getContent())>1048576,413);
  abort_unless(Webhooks::signature($r->getContent(),$r->header('X-Hub-Signature-256'),$secrets['app_secret']??null),403);
  $body=json_decode($r->getContent(),true);abort_unless(is_array($body),400);
  Webhooks::ingest($b,$body);return response()->json(['received'=>true]);
 }
}
