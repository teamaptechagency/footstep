<?php
namespace App\Services;

use App\Models\BusinessSetting;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

class MetaPixel
{
    public const KEY='meta_pixel_v2';
    private $options;
    private $directory;
    private $crypto;

    public function __construct($options=null,$directory=null,$crypto=null)
    {
        $this->options=$options??self::settings();
        $this->directory=$directory??dirname(base_path()).'/footstep-meta-data';
        $this->crypto=$crypto??app('encrypter');
    }

    public static function settings()
    {
        return Cache::remember('meta_pixel_v2',3600,function(){
            $stored=json_decode((string)BusinessSetting::where('type',self::KEY)->value('value'),true);
            $managed=TrackingIntegrations::settings();
            $legacy=$managed['meta']??['id'=>(string)env('FACEBOOK_PIXEL_ID',''),'enabled'=>get_setting('facebook_pixel')==1];
            return array_replace(['id'=>$legacy['id']??'','enabled'=>(bool)($legacy['enabled']??false),'browser'=>true,'capi'=>false,'advanced_matching'=>false,'require_consent'=>true,'exclude_admin'=>true,'cod_event'=>'Purchase','dispatch_limit'=>10,'graph_version'=>'v26.0','test_code'=>'','catalog_enabled'=>false,'events'=>array_fill_keys(MetaEventData::EVENTS,true)],is_array($stored)?$stored:[]);
        });
    }

    public static function currency()
    {
        try{$base=get_system_default_currency()->code??'BDT';}catch(\Throwable $e){$base='BDT';}return MetaEventData::currency(session('currency_code',$base),$base);
    }

    public function enabled()
    {
        return !empty($this->options['enabled'])&&preg_match('/^[0-9]{5,25}$/D',$this->options['id']??'');
    }

    public function allowed($request)
    {
        if(!$this->enabled()||$this->sensitive($request)||$request->header('DNT')==='1'||$request->header('Sec-GPC')==='1')return false;
        if($request->cookie('footstep_marketing')==='denied')return false;
        if(!empty($this->options['require_consent'])&&$request->cookie('footstep_marketing')!=='granted')return false;
        if(!empty($this->options['exclude_admin'])&&$request->user()&&in_array($request->user()->user_type,['admin','staff'],true))return false;
        return true;
    }

    public function browserConfig($request)
    {
        $options=$this->options;
        $choice=$request->cookie('footstep_marketing');
        $options['consent']=in_array($choice,['granted','denied'],true)?$choice:'unset';
        $options['enabled']=(bool)$this->enabled();$options['currency']=self::currency();
        if($this->sensitive($request))$options['enabled']=false;
        if(!empty($options['exclude_admin'])&&$request->user()&&in_array($request->user()->user_type,['admin','staff'],true))$options['enabled']=false;
        // No token, API credentials, test-event code or customer information in JavaScript.
        return array_intersect_key($options,array_flip(['id','enabled','browser','capi','require_consent','events','cod_event','currency','consent']));
    }

    public function envelope($name,array $data=[],$stable=null,array $matching=[],$time=null,$serverCapture=true)
    {
        if(!$this->enabled()||!in_array($name,MetaEventData::EVENTS,true)||empty($this->options['events'][$name]))return null;
        $request=request();
        if($this->sensitive($request))return null;
        if(!empty($this->options['exclude_admin'])&&$request->user()&&in_array($request->user()->user_type,['admin','staff'],true))return null;
        $data=MetaEventData::money($data,self::currency(),$name==='Purchase');
        $time=$time??time();if($time<time()-86400)return null;
        $id=$stable===null?'fp-'.bin2hex(random_bytes(16)):'fp-'.hash_hmac('sha256',$this->options['id'].'|'.$name.'|'.$stable,config('app.key'));
        $source=$request->url();$referer=$request->header('Referer');if(!$request->isMethod('GET')&&$referer&&parse_url($referer,PHP_URL_HOST)===$request->getHost())$source=$referer;
        if(!preg_match('~^/(?:$|product/|category/|brand/|search$|cart(?:/|$)|checkout(?:/|$)|order-confirmed$|blog(?:/|$))~',parse_url($source,PHP_URL_PATH)?:'/'))$source=$request->getSchemeAndHttpHost().'/';
        $event=['event_name'=>$name,'event_id'=>$id,'event_time'=>$time,'action_source'=>'website','event_source_url'=>MetaEventData::safeUrl($source),'custom_data'=>$data];
        if(!$matching&&$request->user()&&!in_array($request->user()->user_type,['admin','staff'],true))$matching=['email'=>$request->user()->email,'phone'=>$request->user()->phone,'external_id'=>(string)$request->user()->id];
        $ticket=['event'=>$event,'pixel'=>$this->options['id'],'session'=>hash('sha256',$request->session()->getId()),'issued'=>time(),'matching'=>!empty($this->options['advanced_matching'])?MetaEventData::matching($matching):[]];
        $encrypted=$this->crypto->encryptString(json_encode($ticket));
        // Capture confirmed server actions even if an ad blocker stops browser JS.
        // Prepared Contact/AddPaymentInfo tickets opt out until their actual action.
        if($serverCapture){try{$this->queueTicket($encrypted,$request);}catch(\Throwable $e){}}
        return ['name'=>$name,'id'=>$id,'data'=>$data,'stable'=>$stable!==null,'match'=>$this->allowed($request)?$ticket['matching']:[],'ticket'=>$encrypted];
    }

    public static function event($name,array $data=[],$stable=null,array $matching=[],$time=null,$serverCapture=true)
    {
        try{return app(self::class)->envelope($name,$data,$stable,$matching,$time,$serverCapture);}catch(\Throwable $e){return null;}
    }

    public function queueTicket($ticket,$request)
    {
        if(!$this->allowed($request))return 'opted_out';
        if(empty($this->options['capi'])||!$this->hasToken())return 'browser_only';
        try{$data=json_decode($this->crypto->decryptString($ticket),true,512,JSON_THROW_ON_ERROR);}catch(\Throwable $e){throw new RuntimeException('Invalid event ticket.');}
        if(!is_array($data)||($data['pixel']??'')!==$this->options['id']||!hash_equals(hash('sha256',$request->session()->getId()),$data['session']??'')||($data['issued']??0)<time()-21600||($data['issued']??PHP_INT_MAX)>time()+30)throw new RuntimeException('Expired or wrong-session event ticket.');
        $event=$data['event']??[];$name=$event['event_name']??'';
        if(!in_array($name,MetaEventData::EVENTS,true)||empty($this->options['events'][$name])||($event['event_time']??0)<time()-86400)throw new RuntimeException('Disabled or expired event.');
        $user=[];
        if(!empty($this->options['advanced_matching']))$user=$data['matching']??[];
        foreach(['_fbp'=>'fbp','_fbc'=>'fbc'] as $cookie=>$field){$value=MetaEventData::cookie($request->cookie($cookie));if($value)$user[$field]=$value;}
        $ip=$request->ip();if(filter_var($ip,FILTER_VALIDATE_IP))$user['client_ip_address']=$ip;
        $user['client_user_agent']=mb_substr((string)$request->userAgent(),0,512);
        $event['custom_data']=MetaEventData::money($event['custom_data']??[],self::currency(),$name==='Purchase');
        $event['user_data']=$user;
        return $this->enqueue($event,$data['session'],$request->cookie('footstep_marketing')==='granted');
    }

    public function enqueue(array $event,$binding='diagnostic',$consent=true)
    {
        $event['custom_data']=MetaEventData::money($event['custom_data']??[],self::currency(),($event['event_name']??'')==='Purchase');
        $this->ensureDirectories();$key=hash('sha256',$this->options['id'].'|'.$event['event_name'].'|'.$event['event_id']);
        $lock=fopen($this->directory.'/locks/'.$key.'.lock','c');if(!$lock||!flock($lock,LOCK_EX))throw new RuntimeException('Cannot lock event.');
        try{
            if(is_file($this->directory.'/outbox/'.$key.'.enc')||is_file($this->directory.'/receipts/'.$key.'.json'))return 'duplicate';
            $payload=['event'=>$event,'pixel'=>$this->options['id'],'binding'=>$binding,'consent'=>$consent,'captured_at'=>microtime(true),'attempts'=>0,'next'=>0,'test_code'=>$this->options['test_code']??''];
            $this->atomic($this->directory.'/outbox/'.$key.'.enc',$this->crypto->encryptString(json_encode($payload)));
            MetaDeliveryReport::record($this->directory,'queued',$event['event_name'],0,!empty($payload['test_code']));
            return 'queued';
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }

    public function revoke($sessionId)
    {
        $this->ensureDirectories();$this->atomic($this->directory.'/revocations/'.hash('sha256',$sessionId).'.txt',(string)microtime(true));
    }

    public function hasToken(){return is_file($this->directory.'/access-token.enc');}
    public function token(){return $this->hasToken()?$this->crypto->decryptString(file_get_contents($this->directory.'/access-token.enc')):null;}
    public function setToken($token){$this->ensureDirectories();$this->atomic($this->directory.'/access-token.enc',$this->crypto->encryptString($token));}
    public function removeToken(){if($this->hasToken())unlink($this->directory.'/access-token.enc');}
    public function catalogKey(){ $this->ensureDirectories();$file=$this->directory.'/catalog-key.enc';if(!is_file($file))$this->atomic($file,$this->crypto->encryptString(bin2hex(random_bytes(32))));return $this->crypto->decryptString(file_get_contents($file));}

    private function sensitive($request){return $request->is('admin','admin/*','api/*','password/*','email/*','email_change/*','verification*','social-login/*')||$request->query->has('token');}

    public function diagnostics()
    {
        $this->ensureDirectories();$receipts=glob($this->directory.'/receipts/*.json')?:[];usort($receipts,function($a,$b){return filemtime($b)<=>filemtime($a);});$recent=[];
        foreach(array_slice($receipts,0,20) as $file){$r=json_decode(file_get_contents($file),true);if(is_array($r))$recent[]=$r;}
        usort($recent,function($a,$b){return strcmp($b['at']??'',$a['at']??'');});
        $scheduler=(bool)(config('server_operations.meta_capi_dispatch')||config('server_operations.live_integrations'));
        $blockers=[];
        if(!$this->enabled())$blockers[]='Enable Meta tracking and configure a Dataset ID.';
        if(empty($this->options['capi']))$blockers[]='Conversions API is disabled.';
        if(!$this->hasToken())$blockers[]='Save a Conversions API access token.';
        if(!$scheduler)$blockers[]='Meta CAPI scheduling is disabled.';
        if(!empty($this->options['require_consent']))$blockers[]='Only visitors granting marketing consent are tracked.';
        return ['scheduler_enabled'=>$scheduler,'setup_blockers'=>$blockers,'pending'=>count(glob($this->directory.'/outbox/*.enc')?:[]),'failed'=>count(glob($this->directory.'/failed/*.enc')?:[]),'token_stored'=>$this->hasToken(),'last_worker'=>is_file($this->directory.'/worker-heartbeat')?file_get_contents($this->directory.'/worker-heartbeat'):null,'recent'=>$recent];
    }

    /** $sender returns only status/events_received/error_code/transient; never log raw HTTP bodies. */
    public function dispatch($sender,$limit=10,$budget=45)
    {
        $this->ensureDirectories();
        if(!$this->enabled()||empty($this->options['capi'])||!$this->hasToken())return 0;
        $lock=fopen($this->directory.'/worker.lock','c');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))return 0;
        $processed=0;$deadline=microtime(true)+max(1,min(50,(int)$budget));
        try{
            $this->atomic($this->directory.'/worker-heartbeat',gmdate('c'));
            foreach(glob($this->directory.'/outbox/*.enc')?:[] as $file){
                if($processed>=max(1,min(100,$limit))||microtime(true)>=$deadline)break;
                $key=basename($file,'.enc');
                try{$payload=json_decode($this->crypto->decryptString(file_get_contents($file)),true,512,JSON_THROW_ON_ERROR);if(!isset($payload['event']['event_name'],$payload['pixel'],$payload['binding'],$payload['next'],$payload['captured_at']))throw new RuntimeException('Corrupt event.');}catch(\Throwable $e){rename($file,$this->directory.'/failed/'.$key.'.enc');continue;}
                if($payload['next']>time())continue;
                $revoked=$this->directory.'/revocations/'.$payload['binding'].'.txt';
                if($payload['pixel']!==$this->options['id']||empty($this->options['events'][$payload['event']['event_name']])||$payload['event']['event_time']<time()-86400||(!empty($this->options['require_consent'])&&empty($payload['consent']))||(is_file($revoked)&&(float)file_get_contents($revoked)>=$payload['captured_at'])){
                    $this->receipt($key,$payload,'discarded',0);unlink($file);continue;
                }
                // Development URLs cannot send production conversions accidentally.
                $host=parse_url($payload['event']['event_source_url'],PHP_URL_HOST);
                if(in_array($host,['localhost','127.0.0.1','::1'],true)&&empty($payload['test_code'])){
                    $this->receipt($key,$payload,'local_blocked',0);rename($file,$this->directory.'/failed/'.$key.'.enc');continue;
                }
                try{if($payload['event']['event_name']==='Purchase'&&(!is_string($payload['event']['custom_data']['currency']??null)||!preg_match('/^[A-Z]{3}$/D',strtoupper(trim($payload['event']['custom_data']['currency'])))))throw new \InvalidArgumentException('Queued Purchase currency is unknown; do not guess historical currency.');$payload['event']['custom_data']=MetaEventData::money($payload['event']['custom_data']??[],self::currency(),$payload['event']['event_name']==='Purchase');}catch(\InvalidArgumentException $e){$this->receipt($key,$payload,'invalid_money',0);rename($file,$this->directory.'/failed/'.$key.'.enc');continue;}
                if(empty($this->options['advanced_matching']))foreach(['em','ph','external_id','fn','ln','ct','st','zp','country'] as $field)unset($payload['event']['user_data'][$field]);
                try{$result=$sender($payload,$this->token(),$this->options['graph_version']);}catch(\Throwable $e){$result=['status'=>0,'events_received'=>0,'error_code'=>0,'transient'=>true];}
                $payload['attempts']++;$processed++;
                MetaDeliveryReport::record($this->directory,'api_attempt',$payload['event']['event_name'],(int)$result['status'],!empty($payload['test_code']),$result['error_code']??0);
                if($result['status']>=200&&$result['status']<300&&($result['events_received']??0)>0){$this->receipt($key,$payload,'sent',$result['status']);unlink($file);}
                elseif($payload['attempts']<6&&($result['status']===0||$result['status']===429||$result['status']>=500||!empty($result['transient']))){MetaDeliveryReport::record($this->directory,'retry',$payload['event']['event_name'],(int)$result['status'],!empty($payload['test_code']),$result['error_code']??0);$payload['next']=time()+min(3600,30*(2**$payload['attempts']));$this->atomic($file,$this->crypto->encryptString(json_encode($payload)));}
                else{$this->receipt($key,$payload,'failed',$result['status'],$result['error_code']??0);rename($file,$this->directory.'/failed/'.$key.'.enc');}
            }
        }finally{flock($lock,LOCK_UN);fclose($lock);}
        return $processed;
    }

    public function retry($key)
    {
        if(!preg_match('/^[a-f0-9]{64}$/D',$key))throw new RuntimeException('Invalid event reference.');
        $file=$this->directory.'/failed/'.$key.'.enc';if(!is_file($file))throw new RuntimeException('Failed event not found.');
        $payload=json_decode($this->crypto->decryptString(file_get_contents($file)),true);
        if($payload['pixel']!==$this->options['id']||$payload['event']['event_time']<time()-86400)throw new RuntimeException('Expired or different-pixel event cannot be retried.');
        $payload['attempts']=0;$payload['next']=0;$payload['test_code']=$this->options['test_code']??'';
        $this->atomic($this->directory.'/outbox/'.$key.'.enc',$this->crypto->encryptString(json_encode($payload)));unlink($file);
        if(is_file($this->directory.'/receipts/'.$key.'.json'))unlink($this->directory.'/receipts/'.$key.'.json');
    }

    private function receipt($key,$payload,$state,$status,$error=0){MetaDeliveryReport::record($this->directory,$state,$payload['event']['event_name'],(int)$status,!empty($payload['test_code']),$error);$this->atomic($this->directory.'/receipts/'.$key.'.json',json_encode(['key'=>$key,'event'=>$payload['event']['event_name'],'event_id'=>$payload['event']['event_id'],'state'=>$state,'http_status'=>(int)$status,'error_code'=>(int)$error,'attempts'=>$payload['attempts'],'at'=>gmdate('c')]));}
    private function ensureDirectories(){foreach(['outbox','failed','receipts','locks','revocations'] as $dir){$path=$this->directory.'/'.$dir;if(!is_dir($path)&&!mkdir($path,0700,true))throw new RuntimeException('Private Meta storage is not writable.');}}
    private function atomic($path,$bytes){$tmp=$path.'.'.bin2hex(random_bytes(5));if(file_put_contents($tmp,$bytes,LOCK_EX)!==strlen($bytes)||!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Cannot store Meta event.');}chmod($path,0600);}
}
