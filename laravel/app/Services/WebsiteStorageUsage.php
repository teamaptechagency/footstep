<?php
namespace App\Services;

/** On-demand resumable metadata scan of website/managed folders, never the entire hosting OS. */
class WebsiteStorageUsage
{
    private $root;
    private $data;
    public function __construct($root,$backupData)
    {
        $this->root=realpath($root);$this->data=realpath($backupData);
        if(!$this->root||!$this->data||is_link($backupData))throw new \RuntimeException('Storage scan folder unavailable.');
    }
    private function roots()
    {
        $roots=['website'=>$this->root,'full-backups'=>$this->data];
        foreach(glob(dirname($this->root).'/footstep-*',GLOB_ONLYDIR)?:[]as $path){
            $real=realpath($path);if(is_link($path)||!$real||$real===$this->data||$real===$this->root)continue;
            $roots[basename($path)]=$real;
        }
        return $roots;
    }
    private function inside($path,$root)
    {
        $path=str_replace('\\','/',$path);$root=rtrim(str_replace('\\','/',$root),'/');
        return strcasecmp($path,$root)===0||strncasecmp($path,$root.'/',strlen($root)+1)===0;
    }
    private function statePath($id)
    {
        if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))throw new \RuntimeException('Invalid scan reference.');
        return $this->data.'/usage-scan-'.$id.'.json';
    }
    private function save($state)
    {
        $path=$this->statePath($state['id']);if(is_link($path))throw new \RuntimeException('Unsafe scan state.');
        $temp=$path.'.tmp';if(is_link($temp))throw new \RuntimeException('Unsafe scan temporary file.');$json=json_encode($state,JSON_INVALID_UTF8_SUBSTITUTE|JSON_UNESCAPED_SLASHES);
        if(file_put_contents($temp,$json,LOCK_EX)===false||!@rename($temp,$path))throw new \RuntimeException('Cannot save storage scan. Free a little hosting space first; failed backup deletion can still be used.');
        @chmod($path,0600);
    }
    public function start()
    {
        // These are scanner-owned metadata files, not backups or restore data.
        foreach(glob($this->data.'/usage-scan-*.json')?:[]as $path)if(!is_link($path)&&preg_match('/^usage-scan-[a-f0-9]{32}\.json$/D',basename($path))&&filemtime($path)<time()-86400)@unlink($path);
        $id=bin2hex(random_bytes(16));$state=['id'=>$id,'started_at'=>time(),'updated_at'=>time(),'queue'=>[],'current'=>null,'files'=>0,'directories'=>0,'bytes'=>0,'skipped'=>0,'groups'=>[],'archives'=>[],'archive_count'=>0,'done'=>false];
        foreach($this->roots()as $label=>$root)$state['queue'][]=['mount'=>$label,'relative'=>''];
        $this->save($state);return $this->summary($state);
    }
    private function target($roots,$item)
    {
        $mount=$item['mount']??'';$relative=$item['relative']??null;
        if(!isset($roots[$mount])||!is_string($relative)||strpos($relative,"\0")!==false||strpos($relative,'\\')!==false||preg_match('~(^|/)\.\.?(/|$)|^/|:~',$relative))return null;
        $path=$roots[$mount].($relative!==''?'/'.$relative:'');
        if(is_link($path))return null;$real=realpath($path);
        return $real&&$this->inside($real,$roots[$mount])?$real:null;
    }
    public function step($id,$maximum=2000)
    {
        $path=$this->statePath($id);if(is_link($path)||!is_file($path)||filesize($path)>16777216)throw new \RuntimeException('Storage scan expired. Start a new scan.');
        $lockPath=$this->data.'/usage-scan.lock';if(is_link($lockPath))throw new \RuntimeException('Unsafe scan lock.');
        $lock=fopen($lockPath,'c+');if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){if($lock)fclose($lock);throw new \RuntimeException('Storage scan is busy. Retry shortly.');}
        try{
            $state=json_decode(file_get_contents($path),true);
            if(!is_array($state)||($state['id']??null)!==$id)throw new \RuntimeException('Invalid storage scan.');
            if($state['done'])return $this->summary($state);
            if($state['started_at']<time()-1800)throw new \RuntimeException('Scan time limit reached. Start a new scan.');
            $roots=$this->roots();$until=microtime(true)+1.5;$processed=0;$limit=max(1,min(5000,(int)$maximum));
            while($processed<$limit&&microtime(true)<$until){
                if(!$state['current']){
                    if(!$state['queue']){$state['done']=true;break;}
                    $state['current']=array_pop($state['queue']);$state['current']['offset']=0;
                }
                $current=$state['current'];$directory=$this->target($roots,$current);$handle=$directory?@opendir($directory):false;
                if(!$handle){$state['skipped']++;$state['current']=null;continue;}
                $offset=0;while($offset<$current['offset']&&readdir($handle)!==false)$offset++;
                $ended=false;
                try{while($processed<$limit&&microtime(true)<$until){
                    $name=readdir($handle);if($name===false){$ended=true;break;}$state['current']['offset']++;$processed++;
                    if($name==='.'||$name==='..')continue;
                    if($current['mount']==='full-backups'&&preg_match('/^usage-scan-[a-f0-9]{32}\.json(?:\.tmp)?$/D',$name))continue;
                    $relative=ltrim($current['relative'].'/'.$name,'/');$item=['mount'=>$current['mount'],'relative'=>$relative];$target=$this->target($roots,$item);
                    if(!$target){$state['skipped']++;continue;}
                    if(is_dir($target)){$state['directories']++;if(count($state['queue'])>=20000)throw new \RuntimeException('Too many directories for this scan. Hosting Disk Usage is required.');$state['queue'][]=$item;continue;}
                    if(!is_file($target))continue;$size=@filesize($target);if($size===false){$state['skipped']++;continue;}
                    $state['files']++;$state['bytes']+=$size;$parts=explode('/',$relative);
                    $bucket=count($parts)>1?implode('/',array_slice($parts,0,min(3,count($parts)-1))):'(root files)';$key=$current['mount'].'/'.$bucket;
                    if(!isset($state['groups'][$key]))$state['groups'][$key]=['path'=>$key,'bytes'=>0,'files'=>0];$state['groups'][$key]['bytes']+=$size;$state['groups'][$key]['files']++;
                    if(preg_match('/\.(zip|tgz|tar|gz|7z|sql)$/iD',$name)){
                        $state['archive_count']++;$state['archives'][]=['path'=>$current['mount'].'/'.$relative,'bytes'=>$size,'modified_at'=>gmdate('c',filemtime($target))];
                        if(count($state['archives'])>200){usort($state['archives'],function($a,$b){return $b['bytes']<=>$a['bytes'];});$state['archives']=array_slice($state['archives'],0,200);}
                    }
                    if($state['files']>1000000)throw new \RuntimeException('Scan file limit exceeded. Use hosting Disk Usage.');
                }}finally{closedir($handle);}
                if($ended)$state['current']=null;
            }
            $state['updated_at']=time();$this->save($state);return $this->summary($state);
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
    private function summary($state)
    {
        $groups=array_values($state['groups']);usort($groups,function($a,$b){return $b['bytes']<=>$a['bytes'];});
        $archives=$state['archives'];usort($archives,function($a,$b){return $b['bytes']<=>$a['bytes'];});
        return ['id'=>$state['id'],'done'=>$state['done'],'files'=>$state['files'],'directories'=>$state['directories'],'bytes'=>$state['bytes'],'skipped'=>$state['skipped'],'groups'=>array_slice($groups,0,300),'archives'=>$archives,'archive_count'=>$state['archive_count'],'filesystem_free_bytes'=>@disk_free_space($this->root),'filesystem_total_bytes'=>@disk_total_space($this->root),'scope'=>'Website plus sibling footstep-* managed folders only. Logical file sizes; not database-server storage or whole cPanel account quota. Symlinks are skipped. File changes during scanning may affect totals.','updated_at'=>gmdate('c',$state['updated_at'])];
    }
}
