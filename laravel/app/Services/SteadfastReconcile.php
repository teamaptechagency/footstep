<?php
namespace App\Services;
use App\Models\Order;
use Illuminate\Support\Facades\{Cache,Http};
class SteadfastReconcile {
 public static function queue($pattern='code'){
  $max=(int)Order::max('id');StoreOperations::put('steadfast_reconcile',['state'=>'queued','cursor'=>0,'max_id'=>$max,'pattern'=>$pattern==='fs_id'?'fs_id':'code','total'=>(int)Order::where('id','<=',$max)->count(),'checked'=>0,'matched'=>0,'missing'=>0,'skipped'=>0,'error'=>null,'updated_at'=>now()->toIso8601String()]);
 }
 public static function run($limit=5,$deadline=null){
  $settings=StoreOperations::settings();if(!SteadfastAccounts::credentials(SteadfastAccounts::current())['active']||$settings['mode']!=='live'||empty($settings['api_key'])||empty($settings['secret_key']))return;
  $job=StoreOperations::get('steadfast_reconcile');if(!in_array($job['state']??'',['queued','running'],true))return;
  $lock=Cache::lock('steadfast-old-order-reconcile',65);if(!$lock->get())return;
  try{
   $rows=Order::where('id','>',$job['cursor'])->where('id','<=',$job['max_id'])->orderBy('id')->limit(max(1,min(10,$limit)))->get();
   $job['state']='running';$job['error']=null;unset($job['network_failure']);
   if($rows->isEmpty())$job['state']='complete';
   foreach($rows as $order){
    if($deadline&&microtime(true)>=$deadline)break;
    $existing=StoreOperations::shipment($order->id);
    if($existing&&!($existing['mock']??false)){
     if(($existing['account_id']??'legacy')!==SteadfastAccounts::current()){$job['skipped']=($job['skipped']??0)+1;$job['checked']++;$job['cursor']=$order->id;continue;}
     try{StoreOperations::sync($order->id,true);$job['matched']++;$job['checked']++;$job['cursor']=$order->id;}catch(\Throwable $e){$job['error']='linked_status_failed';break;}
     continue;
    }
    $reference=$job['pattern']==='fs_id'?'FS-'.$order->id:(string)$order->code;
    if(!preg_match('/^[A-Za-z0-9_-]{1,100}$/D',$reference)||($job['pattern']==='code'&&Order::where('code',$reference)->count()!==1)){$job['missing']++;$job['cursor']=$order->id;continue;}
    try{
     $response=Http::withHeaders(['Api-Key'=>$settings['api_key'],'Secret-Key'=>$settings['secret_key']])->acceptJson()->withOptions(['connect_timeout'=>3,'allow_redirects'=>false])->timeout(8)->get(SteadfastApi::BASE_URL.'/status_by_invoice/'.rawurlencode($reference));
     $data=$response->json()?:[];
     if(in_array($response->status(),[401,403],true)){$job['state']='blocked';$job['error']='history_access_denied_http_'.$response->status();break;}
     if($response->status()===429){$job['error']='provider_rate_limit';break;}
     if($response->status()===404||($data['status']??null)===404){$job['missing']++;}
     elseif(!$response->successful()||($data['status']??null)!==200||!isset($data['delivery_status'])){$job['error']='provider_unavailable';break;}
     else{
      $invoice=$data['invoice']??($data['consignment']['invoice']??$reference);$phone=$data['recipient_phone']??($data['consignment']['recipient_phone']??null);$address=json_decode($order->shipping_address,true)?:[];
      if((string)$invoice!==$reference||($phone&&OrderProtection::phone($phone)!==OrderProtection::phone($address['phone']??''))){$job['error']='identity_mismatch';$job['missing']++;}
      else{
       $known=['pending','delivered_approval_pending','partial_delivered_approval_pending','cancelled_approval_pending','unknown_approval_pending','delivered','partial_delivered','cancelled','hold','in_review','unknown'];
       if($data['delivery_status']==='unknown'){$job['missing']++;$job['checked']++;$job['cursor']=$order->id;continue;}
       if(!in_array($data['delivery_status'],$known,true)){$job['error']='unknown_status';break;}
       \Illuminate\Support\Facades\DB::transaction(function()use($order,$reference,$data){$locked=Order::whereKey($order->id)->lockForUpdate()->firstOrFail();$already=StoreOperations::shipment($order->id);if($already&&!($already['mock']??false))throw new \RuntimeException('Already linked in another sync.');StoreOperations::put('shipment_'.$order->id,['state'=>'booked','account_id'=>SteadfastAccounts::current(),'account_label'=>StoreOperations::settings()['label'],'mock'=>false,'invoice'=>$reference,'status'=>$data['delivery_status'],'linked_by'=>'exact_invoice','synced_at'=>now()->toIso8601String()]);
       StoreOperations::applyStatus($order->id,$data['delivery_status'],true);});$job['matched']++;
      }
     }
    }catch(\Throwable $e){$job['error']='sync_failed';$job['network_failure']=CourierNetworkFailure::classify($e);break;}
    $job['checked']++;$job['cursor']=$order->id;usleep(1000000);
   }
   $job['updated_at']=now()->toIso8601String();StoreOperations::put('steadfast_reconcile',$job);
  }finally{$lock->release();}
 }
}
