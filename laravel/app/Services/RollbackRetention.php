<?php
namespace App\Services;
class RollbackRetention {
 private static function record($path){$e=json_decode((string)file_get_contents($path),true);if(!is_array($e)||!isset($e['payload'],$e['mac'])||!hash_equals(hash_hmac('sha256',$e['payload'],config('app.key')),$e['mac']))throw new \RuntimeException('Backup retention integrity check failed.');return json_decode($e['payload'],true);}
 public static function count(){ $base=dirname(base_path()).'/footstep-update-data';if(!is_file($base.'/state.json'))return 0;$state=self::record($base.'/state.json');$n=0;foreach(array_reverse($state['history']??[]) as $h){if(!preg_match('/^[a-f0-9]{32}$/D',$h['id']??'')||!is_file($base.'/backups/'.$h['id'].'/record.json'))break;if(++$n>=2)break;}return $n;}
 public static function prune(){
  $data=dirname(base_path()).'/footstep-update-data';if(!is_dir($data)||is_link($data)||!is_file($data.'/state.json'))return;
  $lock=fopen($data.'/operation.lock','c+');if(!$lock)return;try{if(!flock($lock,LOCK_EX|LOCK_NB)||is_file($data.'/pending.json'))return;$state=self::record($data.'/state.json');$keep=array_column(array_slice($state['history']??[],-2),'id');$base=$data.'/backups';if(!is_dir($base)||is_link($base))return;$root=realpath($base);
  foreach(glob($base.'/*',GLOB_ONLYDIR)?:[] as $dir){$id=basename($dir);if(!preg_match('/^[a-f0-9]{32}$/D',$id)||in_array($id,$keep,true)||is_link($dir)||!is_file($dir.'/record.json'))continue;$r=self::record($dir.'/record.json');if(($r['id']??null)!==$id)continue;
  $tree=new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir,\FilesystemIterator::SKIP_DOTS),\RecursiveIteratorIterator::CHILD_FIRST);$paths=[];$safe=true;foreach($tree as $f){$real=$f->getRealPath();if($f->isLink()||!$real||strpos(str_replace('\\','/',$real),rtrim(str_replace('\\','/',$root),'/').'/')!==0){$safe=false;break;}$paths[]=[$f->getPathname(),$f->isDir()];}if(!$safe)continue;foreach($paths as $f){if(!($f[1]?@rmdir($f[0]):@unlink($f[0]))){$safe=false;break;}}if($safe)@rmdir($dir);}
  }finally{flock($lock,LOCK_UN);fclose($lock);}
 }
}
