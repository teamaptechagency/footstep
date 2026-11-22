<?php
namespace App\Services;

/** Admin initiated metadata scan. Only obsolete, unregistered generated copies can be removed. */
class OptimizedCopyCleanup
{
    private $public, $data, $key, $manifests, $extraSources, $resolveKey;
    public function __construct($public, $data, $key, $manifests=null, array $extraSources=[], callable $resolveKey=null)
    {
        $this->public=realpath($public);$this->data=$data;$this->key=$key;$this->manifests=$manifests;$this->extraSources=$extraSources;$this->resolveKey=$resolveKey;
        if(!$this->public||is_link($public)||strlen($key)<24)throw new \RuntimeException('Invalid image cleanup configuration.');
        if(!is_dir($data)&&!@mkdir($data,0700,true))throw new \RuntimeException('Cannot create private cleanup storage. Free hosting space first.');
        $this->data=realpath($data);if(is_link($data)||$this->inside($this->data,$this->public))throw new \RuntimeException('Cleanup records must be private.');
    }
    private function inside($path,$root){$p=str_replace('\\','/',$path);$r=rtrim(str_replace('\\','/',$root),'/');return strcasecmp($p,$r)===0||strncasecmp($p,$r.'/',strlen($r)+1)===0;}
    private function name($name){return is_string($name)&&preg_match('/^([a-f0-9]{32})-[0-9]{1,5}\.(jpg|webp)$/D',$name);}
    private function path($relative){
        if(!is_string($relative)||strpos($relative,"\0")!==false||strpos($relative,'\\')!==false||preg_match('~(^|/)\.\.?(/|$)|^/|:~',$relative))return null;
        $path=$this->public;foreach(explode('/',$relative)as $part){if($part==='')continue;$path.='/'.$part;if(is_link($path))return null;}
        $real=realpath($path);return $real&&$this->inside($real,$this->public)?$real:null;
    }
    private function recordPath($id){if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))throw new \RuntimeException('Invalid cleanup scan reference.');return $this->data.'/'.$id.'.json';}
    private function save($s){
        $path=$this->recordPath($s['id']);$tmp=$path.'.tmp';if(is_link($path)||is_link($tmp))throw new \RuntimeException('Unsafe cleanup record.');
        $raw=json_encode($s,JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);$text=json_encode(['payload'=>$raw,'mac'=>hash_hmac('sha256',$raw,$this->key)]);
        if(file_put_contents($tmp,$text,LOCK_EX)===false||!@rename($tmp,$path))throw new \RuntimeException('Cannot save scan; hosting quota may be full.');@chmod($path,0600);
    }
    private function read($id){
        $path=$this->recordPath($id);if(is_link($path)||!is_file($path)||filesize($path)>16777216)throw new \RuntimeException('Scan expired. Run a new scan.');
        $e=json_decode(file_get_contents($path),true);if(!is_array($e)||!is_string($e['payload']??null)||!is_string($e['mac']??null)||!hash_equals(hash_hmac('sha256',$e['payload'],$this->key),$e['mac']))throw new \RuntimeException('Scan integrity check failed.');
        $s=json_decode($e['payload'],true);if(!is_array($s)||($s['id']??null)!==$id||$s['started']<time()-86400)throw new \RuntimeException('Scan expired. Run a new scan.');return $s;
    }
    public function start($days=30){
        if(!in_array((int)$days,[0,7,30,90,180],true))throw new \RuntimeException('Choose all ages, 7, 30, 90 or 180 days.');
        foreach(glob($this->data.'/*.json')?:[]as $f)if(!is_link($f)&&preg_match('/^[a-f0-9]{32}\.json$/D',basename($f))&&filemtime($f)<time()-86400)@unlink($f);
        $s=['id'=>bin2hex(random_bytes(16)),'started'=>time(),'cutoff'=>time()-(int)$days*86400,'days'=>(int)$days,'phase'=>'sources','queue'=>[''],'current'=>null,'keys'=>[],'sources'=>0,'checked'=>0,'skipped'=>0,'offset'=>0,'candidates'=>[],'done'=>false,'truncated'=>false];$this->protectRegistered($s['keys']);$this->save($s);return $this->summary($s);
    }
    private function protectRegistered(&$keys){foreach($this->extraSources as $relative){$path=$this->path($relative);if($path&&is_file($path))$this->sourceKeys($relative,$path,$keys);}}
    private function sourceKeys($relative,$path,&$keys){
        if(!preg_match('/\.(jpe?g|png|webp|bmp|avif)$/iD',$relative))return;
        $stamp=filemtime($path).':'.filesize($path);
        foreach(['responsive-v1:','responsive-v2-q90:']as $prefix)$keys[substr(hash('sha256',$prefix.$relative.':'.$stamp),0,32)]=true;
        if($this->resolveKey){$current=call_user_func($this->resolveKey,$relative);if(is_string($current)&&preg_match('/^[a-f0-9]{32}$/D',$current))$keys[$current]=true;}
        if($this->manifests){
            $file=$this->manifests.'/'.hash('sha256',$relative).'.json';
            if(is_file($file)&&!is_link($file)&&filesize($file)<=32768){$m=json_decode(file_get_contents($file),true);foreach($m['variants']??[]as $variant){if(is_string($variant)&&preg_match('~^uploads/optimized/([a-f0-9]{32})-[0-9]+\.(webp|jpg)$~D',$variant,$match))$keys[$match[1]]=true;}}
        }
    }
    private function walk(&$s,$maximum,$until){
        $processed=0;
        while($processed<$maximum&&microtime(true)<$until){
            if($s['current']===null){if(!$s['queue'])return true;$s['current']=['relative'=>array_pop($s['queue']),'offset'=>0];}
            $relative=$s['current']['relative'];$directory=$this->path($relative);$h=$directory?@opendir($directory):false;
            if(!$h){$s['skipped']++;$s['current']=null;continue;}
            $i=0;while($i++<$s['current']['offset']&&readdir($h)!==false){}
            try{while($processed<$maximum&&microtime(true)<$until){
                $name=readdir($h);if($name===false){$s['current']=null;break;}$s['current']['offset']++;$processed++;
                if($name==='.'||$name==='..')continue;$rel=ltrim($relative.'/'.$name,'/');
                if($rel==='uploads/optimized'||in_array($name,['node_modules','vendor','.git'],true))continue;
                $path=$this->path($rel);if(!$path){$s['skipped']++;continue;}
                if(is_dir($path)){if(count($s['queue'])>=20000)throw new \RuntimeException('Scan directory limit exceeded.');$s['queue'][]=$rel;continue;}
                if(!is_file($path))continue;$s['sources']++;if($s['sources']>250000)throw new \RuntimeException('Source scan limit exceeded. Use hosting tools.');$this->sourceKeys($rel,$path,$s['keys']);
            }}finally{closedir($h);}
        }
        return false;
    }
    public function step($id){
        return $this->locked(function()use($id){
            $s=$this->read($id);if($s['done'])return $this->summary($s);$until=microtime(true)+1.5;
            if($s['phase']==='sources'&&$this->walk($s,1500,$until))$s['phase']='optimized';
            if($s['phase']==='optimized'){
                $dir=$this->path('uploads/optimized');$h=$dir?@opendir($dir):false;
                if(!$h){$s['done']=true;}else{
                    $i=0;while($i++<$s['offset']&&readdir($h)!==false){}$n=0;
                    try{while($n++<1500&&microtime(true)<$until){$name=readdir($h);if($name===false){$s['done']=true;break;}$s['offset']++;
                        if(!$this->name($name))continue;$s['checked']++;$path=$this->path('uploads/optimized/'.$name);if(!$path||!is_file($path)){ $s['skipped']++;continue;}
                        if(isset($s['keys'][substr($name,0,32)])||filemtime($path)>=$s['cutoff'])continue;
                        $s['candidates'][$name]=['name'=>$name,'bytes'=>filesize($path),'mtime'=>filemtime($path)];
                        if(count($s['candidates'])>=2000){$s['truncated']=true;$s['done']=true;break;}
                    }}finally{closedir($h);}
                }
            }
            $this->save($s);return $this->summary($s);
        });
    }
    private function summary($s){return ['id'=>$s['id'],'phase'=>$s['phase'],'done'=>$s['done'],'days'=>$s['days'],'sources'=>$s['sources'],'checked'=>$s['checked'],'skipped'=>$s['skipped'],'truncated'=>$s['truncated'],'bytes'=>array_sum(array_column($s['candidates'],'bytes')),'rows'=>$s['done']?array_values($s['candidates']):[],'candidate_count'=>count($s['candidates'])];}
    private function locked($callback){$path=$this->data.'/operation.lock';if(is_link($path))throw new \RuntimeException('Unsafe cleanup lock.');$h=fopen($path,'c+');if(!$h||!flock($h,LOCK_EX|LOCK_NB)){if($h)fclose($h);throw new \RuntimeException('Another cleanup request is running. Retry later.');}try{return $callback();}finally{flock($h,LOCK_UN);fclose($h);}}
    public function delete($id,array $names,callable $referenced){
        if(!$names||count($names)>3)throw new \RuntimeException('Choose 1–3 copies per batch.');
        return $this->locked(function()use($id,$names,$referenced){
            $s=$this->read($id);if(!$s['done'])throw new \RuntimeException('Finish the scan before deleting.');
            // Re-read current sources/manifests rather than trusting a potentially stale scan.
            $fresh=['queue'=>[''],'current'=>null,'keys'=>[],'sources'=>0,'skipped'=>0];$this->protectRegistered($fresh['keys']);$until=microtime(true)+10;
            do{$done=$this->walk($fresh,5000,$until);}while(!$done&&microtime(true)<$until);
            if(!$done)throw new \RuntimeException('Fresh source verification exceeded time limit; no files deleted.');
            $plans=[];
            foreach(array_unique($names)as $name){
                if(!$this->name($name)||!isset($s['candidates'][$name]))throw new \RuntimeException('Choose a file from this scan.');
                $path=$this->path('uploads/optimized/'.$name);$row=$s['candidates'][$name];clearstatcache(true,$path?:'');
                if(!$path||!is_file($path)||filesize($path)!==$row['bytes']||filemtime($path)!==$row['mtime']||filemtime($path)>=$s['cutoff'])throw new \RuntimeException('An image changed since scanning. Scan again.');
                if(isset($fresh['keys'][substr($name,0,32)])||$referenced('uploads/optimized/'.$name))throw new \RuntimeException('A selected copy is currently referenced; retained.');
                $plans[]=[$path,$row['bytes']];
            }
            $freed=0;foreach($plans as [$path,$bytes]){if(!@unlink($path))throw new \RuntimeException('A file could not be removed. Refresh storage and rescan before retrying.');$freed+=$bytes;}
            return ['deleted'=>count($plans),'freed_bytes'=>$freed];
        });
    }
}
