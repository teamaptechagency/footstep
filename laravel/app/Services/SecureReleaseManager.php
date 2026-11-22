<?php
namespace App\Services;

use RuntimeException;
use ZipArchive;

/** Code-only, offline-signed releases. Never extracts arbitrary archives or runs hooks/SQL. */
class SecureReleaseManager
{
    private $root;
    private $data;
    private $key;
    private $baseline;

    public function __construct($root, $data, $key, $baseline = '1.0.0')
    {
        $this->root = rtrim(str_replace('\\', '/', realpath($root) ?: $root), '/');
        $this->data = rtrim(str_replace('\\', '/', $data), '/');
        $this->key = $key;
        $this->baseline = $baseline;
        if (strlen($key) < 24) throw new RuntimeException('A valid application audit key is required.');
        if (!is_dir($this->data) && !mkdir($this->data, 0700, true)) throw new RuntimeException('Cannot create private update storage.');
        $real = str_replace('\\', '/', realpath($this->data));
        if (strpos(strtolower($real).'/', strtolower($this->root).'/') === 0) throw new RuntimeException('Update storage must be outside the website root.');
        $this->data = $real;
    }

    public function state()
    {
        return $this->readRecord('state.json', ['version'=>$this->baseline,'history'=>[]]);
    }

    public function keyReady()
    {
        if(!is_file($this->data.'/trusted-public.pem'))return false;
        $key=openssl_pkey_get_public(file_get_contents($this->data.'/trusted-public.pem'));
        if(!$key)return false;
        $details=openssl_pkey_get_details($key);
        return $details && $details['type']===OPENSSL_KEYTYPE_RSA && $details['bits']>=2048;
    }

    public function pending()
    {
        return $this->readRecord('pending.json', null);
    }

    public function stage($zipPath)
    {
        $manifest = $this->inspect($zipPath);
        $id = bin2hex(random_bytes(16));
        if (!is_dir($this->data.'/staged')) mkdir($this->data.'/staged',0700,true);
        if (!copy($zipPath, $this->data.'/staged/'.$id.'.zip')) throw new RuntimeException('Cannot stage upload.');
        chmod($this->data.'/staged/'.$id.'.zip',0600);
        return ['id'=>$id,'manifest'=>$manifest];
    }

    public function preview($id)
    {
        return $this->inspect($this->packagePath($id));
    }

    public function inspect($zipPath)
    {
        if (!$this->keyReady()) throw new RuntimeException('No trusted release public key installed.');
        if (!is_file($zipPath) || filesize($zipPath) > 25*1024*1024) throw new RuntimeException('ZIP is missing or exceeds 25 MB.');
        $zip = new ZipArchive;
        if ($zip->open($zipPath) !== true) throw new RuntimeException('Invalid ZIP archive.');
        try {
            if ($zip->numFiles < 3 || $zip->numFiles > 202) throw new RuntimeException('Package must contain 1–200 payload files.');
            $names=[];$total=0;
            for ($i=0;$i<$zip->numFiles;$i++) {
                $stat=$zip->statIndex($i);$name=$stat['name'];
                if (!is_string($name) || !preg_match('~^[A-Za-z0-9_./-]+$~D',$name) || strpos($name,'..')!==false || $name[0]==='/' || substr($name,-1)==='/') throw new RuntimeException('Unsafe archive entry.');
                $normalized=strtolower($name);
                if(isset($names[$normalized])) throw new RuntimeException('Duplicate or case-colliding archive entries.');
                $names[$normalized]=true;
                $opsys=0;$attr=0;
                if($zip->getExternalAttributesIndex($i,$opsys,$attr) && $opsys===3 && (($attr>>16)&0170000)===0120000) throw new RuntimeException('Archive symlinks are forbidden.');
                if($stat['size']>10*1024*1024) throw new RuntimeException('A payload file exceeds 10 MB.');
                $total+=$stat['size'];
                if($total>50*1024*1024 || ($stat['size']>1024*1024 && $stat['size']>max(1,$stat['comp_size'])*200)) throw new RuntimeException('Archive expansion limit exceeded.');
            }
            $raw=$zip->getFromName('manifest.json');$signature=$zip->getFromName('manifest.sig');
            if(!is_string($raw)||strlen($raw)>131072||!is_string($signature)) throw new RuntimeException('Signed manifest is missing.');
            $sig=base64_decode(trim($signature),true);
            $public=openssl_pkey_get_public(file_get_contents($this->data.'/trusted-public.pem'));
            if(!$public || $sig===false || openssl_verify($raw,$sig,$public,OPENSSL_ALGO_SHA256)!==1) throw new RuntimeException('Signature invalid: this is not a trusted release.');
            $m=json_decode($raw,true);
            if(!is_array($m)||($m['app_id']??'')!=='footstep-storefront'||($m['format']??0)!==1) throw new RuntimeException('Wrong application or package format.');
            foreach(['version','from_version','php_min'] as $field) if(!isset($m[$field])||!is_string($m[$field])||!preg_match('/^\d+\.\d+\.\d+$/D',$m[$field])) throw new RuntimeException('Invalid release version metadata.');
            $state=$this->state();
            if($m['from_version']!==$state['version'] || !version_compare($m['version'],$state['version'],'>')) throw new RuntimeException('Wrong base version or downgrade. Use a package for the installed version.');
            if(version_compare(PHP_VERSION,$m['php_min'],'<')) throw new RuntimeException('PHP version is too old for this release.');
            if(!isset($m['files'])||!is_array($m['files'])||count($m['files'])<1||count($m['files'])>200) throw new RuntimeException('Invalid file manifest.');
            $expected=['manifest.json','manifest.sig'];$paths=[];
            foreach($m['files'] as $entry){
                if(!is_array($entry)||!isset($entry['path'],$entry['sha256'])||!array_key_exists('before_sha256',$entry)) throw new RuntimeException('Incomplete file entry.');
                $path=$this->safePath($entry['path']);
                if(isset($paths[strtolower($path)])) throw new RuntimeException('Duplicate manifest paths.');
                $paths[strtolower($path)]=true;
                if(!is_string($entry['sha256'])||!preg_match('/^[a-f0-9]{64}$/D',$entry['sha256']) || ($entry['before_sha256']!==null && (!is_string($entry['before_sha256'])||!preg_match('/^[a-f0-9]{64}$/D',$entry['before_sha256'])))) throw new RuntimeException('Invalid checksum.');
                $payload=$zip->getFromName('payload/'.$path);
                if(!is_string($payload)||!hash_equals($entry['sha256'],hash('sha256',$payload))) throw new RuntimeException('Payload checksum mismatch: '.$path);
                if(substr($path,-4)==='.php' && substr($path,-10)!=='.blade.php') {
                    try{token_get_all($payload,TOKEN_PARSE);}catch(\ParseError $e){throw new RuntimeException('PHP syntax error in '.$path);}
                }
                $target=$this->target($path);
                $current=is_file($target)?hash_file('sha256',$target):null;
                if($current!==$entry['before_sha256']) throw new RuntimeException('Installed file differs from release base: '.$path);
                $expected[]='payload/'.$path;
            }
            sort($expected);$actual=[];for($i=0;$i<$zip->numFiles;$i++)$actual[]=$zip->getNameIndex($i);sort($actual);
            if($actual!==$expected) throw new RuntimeException('Unlisted or missing archive files.');
            return $m;
        } finally {$zip->close();}
    }

    public function install($id, $actor)
    {
        return $this->locked(function () use ($id,$actor) {
            if($this->pending()) throw new RuntimeException('An interrupted update requires CLI recovery first.');
            $zipPath=$this->packagePath($id);$m=$this->inspect($zipPath);$state=$this->state();
            $record=['id'=>bin2hex(random_bytes(16)),'from'=>$state['version'],'to'=>$m['version'],'actor'=>$actor,'at'=>gmdate('c'),'files'=>$m['files'],'state_before'=>$state,'operation'=>'install'];
            $this->snapshot($record);
            $this->writeRecord('pending.json',$record);
            $this->maintenance(true);
            try {
                $zip=new ZipArchive;
                if($zip->open($zipPath)!==true) throw new RuntimeException('Cannot reopen staged package.');
                try{foreach($m['files'] as $entry)$this->atomicWrite($this->target($entry['path']),$zip->getFromName('payload/'.$entry['path']));}finally{$zip->close();}
                foreach($m['files'] as $entry)if(hash_file('sha256',$this->target($entry['path']))!==$entry['sha256'])throw new RuntimeException('Installed checksum verification failed.');
                $state['version']=$m['version'];$state['history'][]=['id'=>$record['id'],'from'=>$record['from'],'to'=>$record['to'],'at'=>$record['at'],'actor'=>$actor];
                $this->writeRecord('state.json',$state);
                $this->writeRecord('event-'.bin2hex(random_bytes(12)).'.json',['operation'=>'Installed','from'=>$record['from'],'to'=>$record['to'],'actor'=>$actor,'at'=>gmdate('c')]);
                $this->clearGeneratedCaches();
                $this->maintenance(false);unlink($this->data.'/pending.json');
                return $record['id'];
            } catch (\Throwable $e) {
                $this->restore($record,false);
                $this->writeRecord('state.json',$record['state_before']);
                $this->maintenance(false);
                if(is_file($this->data.'/pending.json'))unlink($this->data.'/pending.json');
                throw new RuntimeException('Install failed; original files restored. '.$e->getMessage(),0,$e);
            }
        });
    }

    public function rollback($actor)
    {
        return $this->locked(function () use ($actor) {
            if($this->pending())throw new RuntimeException('Recover the interrupted operation first.');
            $state=$this->state();$last=end($state['history']);
            if(!$last)throw new RuntimeException('No installed release to roll back.');
            $record=$this->readRecord('backups/'.$last['id'].'/record.json');
            // Never overwrite edits made after an update.
            foreach($record['files'] as $entry)if(!is_file($this->target($entry['path']))||hash_file('sha256',$this->target($entry['path']))!==$entry['sha256'])throw new RuntimeException('Rollback blocked: a file changed after installation: '.$entry['path']);
            $pending=$record;$pending['operation']='rollback';$pending['actor']=$actor;
            $this->writeRecord('pending.json',$pending);$this->maintenance(true);
            $this->restore($record,false);
            $this->writeRecord('state.json',$record['state_before']);
            $this->writeRecord('rollback-'.bin2hex(random_bytes(8)).'.json',['release'=>$last['id'],'actor'=>$actor,'at'=>gmdate('c')]);
            $this->writeRecord('event-'.bin2hex(random_bytes(12)).'.json',['operation'=>'Rolled back','from'=>$state['version'],'to'=>$record['from'],'actor'=>$actor,'at'=>gmdate('c')]);
            $this->clearGeneratedCaches();$this->maintenance(false);unlink($this->data.'/pending.json');
        });
    }

    public function events()
    {
        $events=[];
        foreach(glob($this->data.'/event-*.json')?:[] as $file)$events[]=$this->readRecord(basename($file));
        usort($events,function($a,$b){return strcmp($b['at'],$a['at']);});
        return $events;
    }

    /** Recovery is deliberately CLI-only if a process is killed midway. */
    public function recover()
    {
        return $this->locked(function(){
            $record=$this->pending();if(!$record)return false;
            $this->restore($record,false);$this->writeRecord('state.json',$record['state_before']);
            $this->clearGeneratedCaches();$this->maintenance(false);unlink($this->data.'/pending.json');return true;
        });
    }

    private function safePath($path)
    {
        if(!is_string($path)||strlen($path)>220||!preg_match('~^(app|config|routes|resources|public/assets)/[A-Za-z0-9_./-]+$~D',$path)||strpos($path,'..')!==false||strpos($path,'//')!==false)throw new RuntimeException('Forbidden payload path.');
        foreach(explode('/',$path) as $part)if($part===''||$part[0]==='.'||substr($part,-1)==='.'||preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(?:\.|$)/i',$part))throw new RuntimeException('Hidden/ambiguous paths are forbidden.');
        $protected=['app/Services/SecureReleaseManager.php','app/Http/Controllers/ReleaseCenterController.php','app/Http/Controllers/UpdateController.php'];
        foreach($protected as $name)if(strcasecmp($path,$name)===0)throw new RuntimeException('Updater files require a separate audited deployment.');
        if(strpos($path,'public/assets/')===0&&!preg_match('/\.(js|css|svg|png|jpe?g|webp|woff2?|ttf|ico|json|map)$/iD',$path))throw new RuntimeException('Executable files cannot be installed in assets.');
        return $path;
    }

    private function target($path)
    {
        $path=$this->safePath($path);$current=$this->root;
        foreach(explode('/',$path) as $part){$current.='/'.$part;if(is_link($current))throw new RuntimeException('Destination symlinks are forbidden.');}
        return $current;
    }

    private function packagePath($id)
    {
        if(!is_string($id)||!preg_match('/^[a-f0-9]{32}$/D',$id))throw new RuntimeException('Invalid package reference.');
        return $this->data.'/staged/'.$id.'.zip';
    }

    private function snapshot($record)
    {
        $dir=$this->data.'/backups/'.$record['id'];
        foreach($record['files'] as $entry){
            $source=$this->target($entry['path']);$backup=$dir.'/files/'.$entry['path'];
            if($entry['before_sha256']!==null){
                if(!is_dir(dirname($backup)))mkdir(dirname($backup),0700,true);
                if(!copy($source,$backup)||hash_file('sha256',$backup)!==$entry['before_sha256'])throw new RuntimeException('Backup failed; installation not started.');
            }
        }
        $this->writeRecord('backups/'.$record['id'].'/record.json',$record);
    }

    private function restore($record,$unused)
    {
        // Verify the entire backup before touching any destination.
        foreach($record['files'] as $entry){$this->target($entry['path']);if($entry['before_sha256']!==null){$p=$this->data.'/backups/'.$record['id'].'/files/'.$entry['path'];if(!is_file($p)||hash_file('sha256',$p)!==$entry['before_sha256'])throw new RuntimeException('Backup integrity check failed; maintenance remains active.');}}
        foreach($record['files'] as $entry){$target=$this->target($entry['path']);if($entry['before_sha256']===null){if(is_file($target)&&!unlink($target))throw new RuntimeException('Cannot remove new file during recovery.');}else{$this->atomicWrite($target,file_get_contents($this->data.'/backups/'.$record['id'].'/files/'.$entry['path']));}}
    }

    protected function atomicWrite($path,$bytes)
    {
        if(!is_string($bytes))throw new RuntimeException('Missing file bytes.');
        if(!is_dir(dirname($path))&&!mkdir(dirname($path),0755,true))throw new RuntimeException('Cannot create destination directory.');
        $mode=is_file($path)?(fileperms($path)&0777):0644;
        $tmp=$path.'.footstep-'.bin2hex(random_bytes(6));
        if(file_put_contents($tmp,$bytes,LOCK_EX)!==strlen($bytes)){@unlink($tmp);throw new RuntimeException('Failed to write file.');}
        chmod($tmp,$mode);
        if(!rename($tmp,$path)){@unlink($tmp);throw new RuntimeException('Atomic file replacement failed.');}
        if(function_exists('opcache_invalidate'))opcache_invalidate($path,true);
    }

    private function writeRecord($name,$data)
    {
        $raw=json_encode($data,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
        $this->atomicWrite($this->data.'/'.$name,json_encode(['payload'=>$raw,'mac'=>hash_hmac('sha256',$raw,$this->key)],JSON_THROW_ON_ERROR));
        chmod($this->data.'/'.$name,0600);
    }

    private function readRecord($name,$default=null)
    {
        if(!is_file($this->data.'/'.$name))return $default;
        $envelope=json_decode(file_get_contents($this->data.'/'.$name),true);
        if(!is_array($envelope)||!isset($envelope['payload'],$envelope['mac'])||!hash_equals(hash_hmac('sha256',$envelope['payload'],$this->key),$envelope['mac']))throw new RuntimeException('Update history integrity check failed.');
        return json_decode($envelope['payload'],true,512,JSON_THROW_ON_ERROR);
    }

    private function locked($callback)
    {
        $lock=fopen($this->data.'/operation.lock','c');
        if(!$lock||!flock($lock,LOCK_EX|LOCK_NB))throw new RuntimeException('Another update operation is running.');
        try{return $callback();}finally{flock($lock,LOCK_UN);fclose($lock);}
    }

    private function maintenance($enabled)
    {
        $file=$this->data.'/maintenance.lock';
        if($enabled){if(file_put_contents($file,'Release maintenance in progress',LOCK_EX)===false)throw new RuntimeException('Cannot enable maintenance.');}
        elseif(is_file($file))unlink($file);
    }

    private function clearGeneratedCaches()
    {
        foreach(['bootstrap/cache/config.php','bootstrap/cache/routes-v7.php','bootstrap/cache/routes.php','bootstrap/cache/events.php'] as $file)if(is_file($this->root.'/'.$file))unlink($this->root.'/'.$file);
        foreach(glob($this->root.'/storage/framework/views/*.php')?:[] as $file)unlink($file);
    }
}
