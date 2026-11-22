<?php
namespace App\Services;

/** Small daily counters only: never event payloads, URLs, tokens, IPs or customer identifiers. */
class MetaDeliveryReport
{
    private const STATES=['queued','api_attempt','sent','failed','retry','discarded','local_blocked','invalid_money','corrupt'];
    public static function record($directory,$state,$event,$http=0,$test=false,$error=0,$time=null)
    {
        if(!in_array($state,self::STATES,true)||!in_array($event,MetaEventData::EVENTS,true))return false;
        $time=$time??time();$day=(new \DateTimeImmutable('@'.$time))->setTimezone(new \DateTimeZone('Asia/Dhaka'))->format('Y-m-d');$dir=$directory.'/daily';
        $lock=null;
        try{
            if(is_link($directory)||is_link($dir)||(!is_dir($dir)&&!@mkdir($dir,0700,true)))return false;
            if(is_link($dir.'/metrics.lock'))return false;$lock=fopen($dir.'/metrics.lock','c+');if(!$lock||!flock($lock,LOCK_EX))return false;
            $path=$dir.'/'.$day.'.json';if(is_link($path))return false;
            $data=is_file($path)?json_decode(file_get_contents($path),true):null;
            if(!is_array($data)||($data['day']??null)!==$day)$data=['day'=>$day,'coverage_from'=>gmdate('c',$time),'production'=>['states'=>[],'events'=>[],'http'=>[],'errors'=>[]],'test'=>['states'=>[],'events'=>[],'http'=>[],'errors'=>[]]];
            $bucket=$test?'test':'production';$data[$bucket]['states'][$state]=($data[$bucket]['states'][$state]??0)+1;
            $data[$bucket]['events'][$event][$state]=($data[$bucket]['events'][$event][$state]??0)+1;
            if($state==='api_attempt'){$status=(int)$http;$status=$status>=100&&$status<=599?$status:0;$data[$bucket]['http'][(string)$status]=($data[$bucket]['http'][(string)$status]??0)+1;}
            if($error>0&&$error<=1000000)$data[$bucket]['errors'][(string)(int)$error]=($data[$bucket]['errors'][(string)(int)$error]??0)+1;
            $data['updated_at']=gmdate('c',$time);$temp=$path.'.tmp';if(is_link($temp))return false;
            if(file_put_contents($temp,json_encode($data),LOCK_EX)===false||!@rename($temp,$path))return false;@chmod($path,0600);
            // Keep 90 small daily reports. Outbox, failed events and receipts are never purged here.
            foreach(glob($dir.'/*.json')?:[]as $old)if(!is_link($old)&&preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}\.json$/D',basename($old))&&basename($old,'.json')<date('Y-m-d',time()-90*86400))@unlink($old);
            return true;
        }catch(\Throwable $e){return false;}finally{if(is_resource($lock)){flock($lock,LOCK_UN);fclose($lock);}}
    }
    public static function summary($directory,$days=7)
    {
        $days=max(1,min(30,(int)$days));$today=(new \DateTimeImmutable('now',new \DateTimeZone('Asia/Dhaka')))->setTime(0,0);$rows=[];$coverage=null;
        for($i=0;$i<$days;$i++){
            $day=$today->modify('-'.$i.' days')->format('Y-m-d');$file=$directory.'/daily/'.$day.'.json';$data=null;
            if(is_file($file)&&!is_link($file)&&filesize($file)<=131072){$data=json_decode(file_get_contents($file),true);if(($data['day']??null)!==$day)$data=null;}
            $rows[]=$data?:['day'=>$day,'coverage_from'=>null,'production'=>['states'=>[],'events'=>[],'http'=>[],'errors'=>[]],'test'=>['states'=>[],'events'=>[],'http'=>[],'errors'=>[]]];
            if($data&&(!$coverage||$data['coverage_from']<$coverage))$coverage=$data['coverage_from'];
        }
        return ['timezone'=>'Asia/Dhaka','days'=>$rows,'coverage_from'=>$coverage,'writable'=>is_dir($directory.'/daily')?is_writable($directory.'/daily'):(is_dir($directory)?is_writable($directory):is_writable(dirname($directory))),'scope'=>'Recorded server CAPI activity since this update; test events are separate. Browser Pixel receipt and ad attribution are not measured here.'];
    }
}
