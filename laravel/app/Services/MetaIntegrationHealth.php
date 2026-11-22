<?php
namespace App\Services;

/** Connection test sends one synthetic Test Events PageView, never an existing order/outbox. */
class MetaIntegrationHealth
{
    private $meta,$options,$directory;
    public function __construct($meta,array $options,$directory){$this->meta=$meta;$this->options=$options;$this->directory=$directory;}
    private function fingerprint(){return hash('sha256',($this->options['id']??'').'|'.($this->options['graph_version']??'').'|'.(!empty($this->options['capi'])?'1':'0').'|'.($this->meta->hasToken()?hash_file('sha256',$this->directory.'/access-token.enc'):''));}
    public function summary($days=7){
        $d=$this->meta->diagnostics();$test=null;$path=$this->directory.'/last-api-test.json';
        if(is_file($path)&&!is_link($path)&&filesize($path)<=8192){$saved=json_decode(file_get_contents($path),true);if(is_array($saved)){$test=array_intersect_key($saved,array_flip(['ok','http','code','at','received']));$test['current']=hash_equals($this->fingerprint(),$saved['fingerprint']??'');}}
        $receipts=[];foreach($d['recent'] as $row)$receipts[]=array_intersect_key($row,array_flip(['event','state','http_status','error_code','attempts','at']));
        return ['settings'=>['enabled'=>!empty($this->options['enabled']),'browser'=>!empty($this->options['browser']),'capi'=>!empty($this->options['capi']),'exclude_admin'=>!empty($this->options['exclude_admin']),'require_consent'=>!empty($this->options['require_consent']),'test_code_stored'=>!empty($this->options['test_code']),'token_stored'=>$d['token_stored'],'dispatch_limit'=>max(1,min(100,(int)($this->options['dispatch_limit']??10)))],'pending'=>$d['pending'],'failed'=>$d['failed'],'last_worker'=>$d['last_worker'],'test'=>$test,'recent'=>$receipts,'daily'=>MetaDeliveryReport::summary($this->directory,$days)];
    }
    public function test(callable $sender,$source){
        if(empty($this->options['enabled'])||empty($this->options['capi'])||!$this->meta->hasToken())throw new \RuntimeException('Enable Meta + CAPI and save an access token first.');
        if(empty($this->options['test_code'])||!preg_match('/^[A-Za-z0-9_-]{1,100}$/D',$this->options['test_code']))throw new \RuntimeException('Meta Events Manager → Test Events থেকে Test Event Code save করুন। Production Purchase test পাঠানো হবে না।');
        if(!preg_match('/^[0-9]{5,25}$/D',$this->options['id']??'')||!preg_match('/^v[0-9]{1,3}\.0$/D',$this->options['graph_version']??''))throw new \RuntimeException('Save a valid Dataset ID / Graph API version first.');
        $this->meta->diagnostics();$id='connection-test-'.bin2hex(random_bytes(16));
        $payload=['pixel'=>$this->options['id'],'test_code'=>$this->options['test_code'],'event'=>['event_name'=>'PageView','event_id'=>$id,'event_time'=>time(),'event_source_url'=>MetaEventData::safeUrl($source),'action_source'=>'website','user_data'=>['external_id'=>[hash('sha256',$id)]]]];
        try{$r=$sender($payload,$this->meta->token(),$this->options['graph_version']);}catch(\Throwable $e){$r=['status'=>0,'events_received'=>0,'error_code'=>0];}
        $http=(int)($r['status']??0);$received=(int)($r['events_received']??0);$code=(int)($r['error_code']??0);$ok=$http>=200&&$http<300&&$received>0;
        MetaDeliveryReport::record($this->directory,'api_attempt','PageView',$http,true,$code);MetaDeliveryReport::record($this->directory,$ok?'sent':'failed','PageView',$http,true,$code);
        $test=['ok'=>$ok,'http'=>$http,'received'=>$received,'code'=>$code,'at'=>gmdate('c'),'fingerprint'=>$this->fingerprint()];$path=$this->directory.'/last-api-test.json';$temp=$path.'.tmp';if(is_link($path)||is_link($temp))throw new \RuntimeException('Unsafe connection test record.');
        if(file_put_contents($temp,json_encode($test),LOCK_EX)===false||!@rename($temp,$path))throw new \RuntimeException('API test attempted but its report could not be saved. Check hosting quota before retrying.');@chmod($path,0600);
        return $this->summary();
    }
}
