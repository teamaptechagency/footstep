<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use App\Services\MigrationOrderBridge;

class SyncMigrationOrders extends Command
{
    protected $signature='migration-orders:sync {--install} {--status} {--disable}';
    protected $description='Temporary read-only old-hosting to VPS order mirror';
    public function handle()
    {
        $dir=storage_path('app/migration-order-bridge');
        if(!is_dir($dir)) mkdir($dir,0700,true);
        $file=$dir.'/connection.json';$stateFile=$dir.'/status.json';
        if($this->option('install')){(new MigrationOrderBridge)->install();$this->info('Bridge mapping table ready. No orders changed.');return 0;}
        if($this->option('status')){$this->line(is_file($stateFile)?file_get_contents($stateFile):'{"status":"not_run"}');return 0;}
        if(!is_file($file)){$this->info('Bridge not configured.');return 0;}
        $cfg=json_decode(file_get_contents($file),true);
        if($this->option('disable')){$cfg['enabled']=false;$this->write($file,$cfg);$this->info('Bridge stopped; source endpoint must also be removed.');return 0;}
        if(empty($cfg['enabled'])){$this->info('Bridge disabled.');return 0;}
        if(time()>(int)($cfg['expires_at']??0)){$this->error('Bridge expired.');return 1;}
        $lock=fopen($dir.'/worker.lock','c');
        if(!flock($lock,LOCK_EX|LOCK_NB)){$this->info('Bridge worker already active.');return 0;}
        $state=is_file($stateFile)?json_decode(file_get_contents($stateFile),true):[];
        $state=is_array($state)?$state:[];$state['last_attempt']=gmdate('c');$counts=[];
        try{
            $parts=parse_url($cfg['url']);
            if(($parts['scheme']??'')!=='https'||($parts['host']??'')!=='footsteponline.com'||!empty($parts['query'])||!empty($parts['user'])||!empty($parts['port'])) throw new \RuntimeException('invalid_source_url');
            if(!filter_var($cfg['origin_ip']??'',FILTER_VALIDATE_IP,FILTER_FLAG_IPV4))throw new \RuntimeException('invalid_origin_ip');
            $key=Crypt::decryptString($cfg['secret_encrypted']);
            for($page=0;$page<3;$page++){
                $after=(int)($state['cursor']??0);$query=http_build_query(['after'=>$after,'limit'=>50]);$ts=(string)time();$nonce=bin2hex(random_bytes(16));
                $responseSignature='';$bytes=0;$tooLarge=false;
                $ch=curl_init($cfg['url'].'?'.$query);
                curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_RESOLVE=>['footsteponline.com:443:'.$cfg['origin_ip']],CURLOPT_HTTPHEADER=>['Accept: application/json','X-Bridge-Time: '.$ts,'X-Bridge-Nonce: '.$nonce,'X-Bridge-Signature: '.hash_hmac('sha256',"GET\n".$query."\n".$ts."\n".$nonce,$key)],CURLOPT_HEADERFUNCTION=>function($ch,$header)use(&$responseSignature){if(stripos($header,'X-Bridge-Signature:')===0)$responseSignature=trim(substr($header,19));return strlen($header);},CURLOPT_NOPROGRESS=>false,CURLOPT_PROGRESSFUNCTION=>function($ch,$total,$download)use(&$tooLarge){if($download>2097152){$tooLarge=true;return 1;}return 0;}]);
                $body=curl_exec($ch);$http=curl_getinfo($ch,CURLINFO_HTTP_CODE);$errno=curl_errno($ch);curl_close($ch);
                if($tooLarge)throw new \RuntimeException('response_too_large');
                if($errno)throw new \RuntimeException('network_error_'.$errno);
                if($http!==200)throw new \RuntimeException('source_http_'.$http);
                if(!is_string($body)||strlen($body)>2097152||!hash_equals(hash_hmac('sha256',$nonce."\n".$body,$key),$responseSignature))throw new \RuntimeException('response_signature_invalid');
                $payload=json_decode($body,true,64,JSON_THROW_ON_ERROR);
                if(($payload['protocol']??0)!==1||($payload['source']??'')!==$cfg['source']||($payload['from']??'')!==$cfg['from']||!is_array($payload['items']??null)||count($payload['items'])>50)throw new \RuntimeException('response_contract_invalid');
                $previous=$after;
                foreach($payload['items'] as $item){
                    $sid=(int)($item['order']['id']??0);if($sid<=$previous)throw new \RuntimeException('response_order_invalid');$previous=$sid;
                    try{$result=(new MigrationOrderBridge)->import($cfg['source'],$item);$counts[$result]=($counts[$result]??0)+1;}
                    catch(\Throwable $e){
                        $reason=in_array($e->getMessage(),['invalid_order','invalid_detail','invalid_addon','product_mapping_mismatch','addon_schema_missing','line_count_changed_requires_review'],true)?$e->getMessage():'import_failed';
                        \Illuminate\Support\Facades\DB::table('migration_order_bridge')->updateOrInsert(['source'=>$cfg['source'],'source_id'=>$sid],['status'=>'error','reason'=>$reason,'synced_at'=>now()]);$counts['error']=($counts['error']??0)+1;
                    }
                }
                if((int)$payload['next']!==($payload['items']?$previous:0))throw new \RuntimeException('response_cursor_invalid');
                $state['cursor']=empty($payload['more'])?0:$previous;
                $state['last_success']=gmdate('c');$state['status']=!empty($counts['error'])||!empty($counts['conflict'])?'review_required':'ok';$state['counts']=$counts;unset($state['error']);
                $this->write($stateFile,$state);
                if(empty($payload['more']))break;
            }
            $this->info('Bridge pass completed: '.json_encode($counts));return 0;
        }catch(\Throwable $e){
            // Never log request headers, customer payload or raw provider exception messages.
            $reason=preg_match('/^(invalid_source_url|invalid_origin_ip|response_[a-z_]+|network_error_[0-9]+|source_http_[0-9]+)$/D',$e->getMessage())?$e->getMessage():'bridge_failed';
            $state['status']='failed';$state['error']=$reason;$this->write($stateFile,$state);$this->error($reason);return 1;
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
    private function write($path,$data){$tmp=$path.'.tmp';if(file_put_contents($tmp,json_encode($data,JSON_PRETTY_PRINT),LOCK_EX)===false)throw new \RuntimeException('state_write_failed');chmod($tmp,0600);if(!rename($tmp,$path))throw new \RuntimeException('state_write_failed');}
}
