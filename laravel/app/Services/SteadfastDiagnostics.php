<?php
namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\{Http,Cache};

class SteadfastDiagnostics
{
    private static function fingerprint($settings){return hash('sha256',($settings['mode']??'').'|'.($settings['api_key']??'').'|'.($settings['secret_key']??''));}
    public static function status(){
        $s=StoreOperations::settings();$test=StoreOperations::get('steadfast_connection_test');$current=!empty($test['fingerprint'])&&hash_equals(self::fingerprint($s),$test['fingerprint']);
        return ['mode'=>$s['mode'],'api_key_stored'=>!empty($s['api_key']),'secret_key_stored'=>!empty($s['secret_key']),'webhook_token_stored'=>!empty($s['webhook_token']),'test'=>$test?array_merge(array_intersect_key($test,array_flip(['ok','code','http','at','curl_errno'])),['current'=>$current]):null,'last_sync'=>StoreOperations::get('steadfast_sync_health'),'worker'=>StoreOperations::get('steadfast_worker_health'),'reconcile'=>StoreOperations::get('steadfast_reconcile'),'runtime'=>['php'=>PHP_VERSION,'curl'=>extension_loaded('curl'),'openssl'=>extension_loaded('openssl'),'ca_file_configured'=>(bool)(ini_get('curl.cainfo')?:ini_get('openssl.cafile'))]];
    }
    public static function test(){
        $s=StoreOperations::settings();$result=['ok'=>false,'code'=>'credentials_missing','http'=>0,'at'=>gmdate('c'),'fingerprint'=>self::fingerprint($s)];
        if($s['mode']!=='live')$result['code']='mock_mode';
        elseif(!empty($s['api_key'])&&!empty($s['secret_key'])){
            try{
                $r=Http::withHeaders(['Api-Key'=>$s['api_key'],'Secret-Key'=>$s['secret_key']])->acceptJson()->withOptions(['connect_timeout'=>3,'allow_redirects'=>false])->timeout(8)->get(SteadfastApi::BASE_URL.'/get_balance');$j=$r->json();$result['http']=$r->status();
                $result['ok']=$r->successful()&&is_array($j)&&(int)($j['status']??0)===200&&isset($j['current_balance'])&&is_numeric($j['current_balance']);
                $result['code']=$result['ok']?'connected':(in_array($r->status(),[401,403],true)?'credentials_rejected':($r->status()===429?'provider_rate_limit':'unexpected_response'));
            }catch(\Throwable $e){$result=array_merge($result,CourierNetworkFailure::classify($e));}
        }
        StoreOperations::put('steadfast_connection_test',$result);return self::status();
    }
    public static function synchronize($origin='manual',$limit=2,$seconds=20){
        $s=StoreOperations::settings();if($s['mode']!=='live'||empty($s['api_key'])||empty($s['secret_key']))throw new \RuntimeException('Live mode এবং saved API Key/Secret Key প্রয়োজন।');
        $lock=Cache::lock('steadfast-health-sync',70);if(!$lock->get())throw new \RuntimeException('আরেকটি sync চলছে। একটু পরে refresh করুন।');
        $health=['at'=>gmdate('c'),'origin'=>$origin==='cron'?'cron':'manual','synced'=>0,'failed'=>0,'state'=>'running'];$deadline=microtime(true)+max(5,min(40,$seconds));
        try{
            $cursor=(int)(StoreOperations::get('steadfast_sync_cursor')['id']??0);$rows=BusinessSetting::whereRaw("type LIKE 'ops!_shipment!_%' ESCAPE '!'")->where('id','>',$cursor)->orderBy('id')->limit(50)->get();$last=$cursor;
            foreach($rows as $row){
                if(microtime(true)>=$deadline||$health['synced']+$health['failed']>=max(1,min(30,$limit)))break;
                $last=$row->id;if(!preg_match('/^ops_shipment_([0-9]+)$/D',$row->type,$m))continue;$parcel=StoreOperations::shipment($m[1]);
                if(($parcel['account_id']??'legacy')!==SteadfastAccounts::current())continue;
                if(!$parcel||!empty($parcel['mock'])||in_array($parcel['status']??'',['delivered','cancelled'],true))continue;
                try{StoreOperations::sync($m[1]);$health['synced']++;}catch(\Throwable $e){$health['failed']++;$health['failure']=CourierNetworkFailure::classify($e);}
            }
            $more=BusinessSetting::whereRaw("type LIKE 'ops!_shipment!_%' ESCAPE '!'")->where('id','>',$last)->exists();StoreOperations::put('steadfast_sync_cursor',['id'=>$more?$last:0]);
            if(microtime(true)<$deadline)SteadfastReconcile::run(2,$deadline);
            $health['state']=$health['failed']?'some_failed':'complete';$health['at']=gmdate('c');StoreOperations::put('steadfast_sync_health',$health);
            return self::status();
        }finally{$lock->release();}
    }
}
