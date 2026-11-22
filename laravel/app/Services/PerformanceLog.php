<?php
namespace App\Services;
class PerformanceLog {
 public static function directory(){return dirname(base_path()).'/footstep-performance-data';}
 public static function settings(){ $p=self::directory().'/settings.json';$s=is_file($p)?json_decode(file_get_contents($p),true):[];return ['enabled'=>($s['enabled']??true)===true,'threshold'=>max(100,min(10000,(int)($s['threshold']??500)))]; }
 public static function health(){
  $dir=self::directory();$settings=$dir.'/settings.json';$lock=$dir.'/write.lock';
  return ['directory_exists'=>is_dir($dir),'writable'=>is_dir($dir)&&is_writable($dir)&&(!is_file($settings)||is_writable($settings))&&(!is_file($lock)||is_writable($lock)), 'settings_saved'=>is_file($settings)];
 }
 private static function prepare(){
  $dir=self::directory();
  if(!is_dir($dir)&&!@mkdir($dir,0700,true)&&!is_dir($dir))throw new \RuntimeException('Performance log storage unavailable.');
  if(!is_writable($dir))throw new \RuntimeException('Performance log storage is not writable.');
 }
 public static function configure($enabled,$threshold){
  self::prepare();$threshold=(int)$threshold;
  if($threshold<100||$threshold>10000)throw new \InvalidArgumentException('Invalid logging threshold.');
  $data=json_encode(['enabled'=>(bool)$enabled,'threshold'=>$threshold]);
  $p=self::directory().'/settings.json';$temp=tempnam(self::directory(),'.settings-');
  if($temp===false)throw new \RuntimeException('Performance settings could not be saved.');
  try{
   if(file_put_contents($temp,$data,LOCK_EX)!==strlen($data)||!@chmod($temp,0600)||!@rename($temp,$p))throw new \RuntimeException('Performance settings could not be saved.');
   clearstatcache(true,$p);
   if(file_get_contents($p)!==$data)throw new \RuntimeException('Performance settings verification failed.');
  }finally{if(is_file($temp))@unlink($temp);}
 }
 public static function append(array $row){self::prepare();$p=self::directory().'/'.gmdate('Y-m-d').'.jsonl';$lock=fopen(self::directory().'/write.lock','c');if(!$lock)return;try{if(!flock($lock,LOCK_EX))return;clearstatcache(true,$p);if(is_file($p)&&filesize($p)>2097152)rename($p,$p.'.previous');file_put_contents($p,json_encode($row,JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND|LOCK_EX);@chmod($p,0600);foreach(glob(self::directory().'/*.jsonl*')?:[]as $old)if(filemtime($old)<time()-7*86400)@unlink($old);}finally{flock($lock,LOCK_UN);fclose($lock);}}
 public static function recent(){ $rows=[];$files=glob(self::directory().'/*.jsonl')?:[];rsort($files);foreach(array_slice($files,0,2)as $p){foreach(array_reverse(file($p,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES))as $line){$r=json_decode($line,true);if(is_array($r))$rows[]=$r;if(count($rows)>=200)return $rows;}}return $rows; }

 public static function stats(){return app()->bound('request')?app('request')->attributes->get('_performance_stats'):null;}
 public static function measure($name,callable $work){$s=self::stats();if(!$s)return $work();$s->depth=$s->depth??[];if(!empty($s->depth[$name]))return $work();$s->depth[$name]=1;$start=microtime(true);try{return $work();}finally{$end=microtime(true);$s->depth[$name]=0;$s->phases=$s->phases??[];$p=$s->phases[$name]??['ms'=>0,'calls'=>0];$p['ms']+=($end-$start)*1000;$p['calls']++;$s->phases[$name]=$p;$s->intervals[]=[max($start,$s->start),$end];}}
 public static function breakdown($s,$end){$ranges=$s->intervals??[];usort($ranges,function($a,$b){return $a[0]<=>$b[0];});$covered=0;$left=null;$right=null;foreach($ranges as $v){$a=max($s->start,$v[0]);$b=min($end,$v[1]);if($b<=$a)continue;if($left===null){$left=$a;$right=$b;}elseif($a<=$right)$right=max($right,$b);else{$covered+=$right-$left;$left=$a;$right=$b;}}if($left!==null)$covered+=$right-$left;$phases=$s->phases??[];foreach($phases as &$p)$p['ms']=round($p['ms'],1);unset($p);return ['phases'=>$phases,'unmeasured_ms'=>round(max(0,($end-$s->start-$covered)*1000),1)];}

 public static function httpStart($request){$s=self::stats();if(!$s)return;$s->http=$s->http??[];$s->http[spl_object_hash($request)]=microtime(true);}
 public static function httpEnd($request){$s=self::stats();if(!$s)return;$key=spl_object_hash($request);if(!isset($s->http[$key]))return;$start=$s->http[$key];unset($s->http[$key]);$end=microtime(true);$p=$s->phases['http_wait']??['ms'=>0,'calls'=>0];$p['ms']+=($end-$start)*1000;$p['calls']++;$s->phases['http_wait']=$p;$s->intervals[]=[$start,$end];}
}
