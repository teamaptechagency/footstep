<?php
namespace App\Services;

/** Disk administration needs no live database connection and never reads ZIP contents. */
class BackupStorageManager
{
    private $root;
    private $data;
    private $key;
    private $jobs;
    private $unknown = false;

    public function __construct($root, $data, $key)
    {
        $this->root = realpath($root);
        $this->data = realpath($data);
        if (!$this->root || !$this->data || is_link($data) || strlen($key) < 24 || $this->within($this->data, $this->root)) throw new \RuntimeException('Private backup folder unavailable or unsafe.');
        $this->key = hash('sha256', $key, true);
    }

    private function within($path, $root)
    {
        $p = str_replace('\\', '/', $path); $r = rtrim(str_replace('\\', '/', $root), '/');
        return strcasecmp($p, $r) === 0 || strncasecmp($p, $r.'/', strlen($r)+1) === 0;
    }

    private function id($id)
    {
        if (!is_string($id) || !preg_match('/^[a-f0-9]{32}$/D', $id)) throw new \RuntimeException('Invalid backup reference.');
        return $id;
    }

    private function readJob($id)
    {
        $path = $this->data.'/'.$this->id($id).'.job';
        if (is_link($path) || !is_file($path)) throw new \RuntimeException('Backup record unavailable.');
        $raw = base64_decode(file_get_contents($path), true);
        if (!$raw || strlen($raw) < 29) throw new \RuntimeException('Backup record invalid.');
        $text = openssl_decrypt(substr($raw,28), 'aes-256-gcm', $this->key, OPENSSL_RAW_DATA, substr($raw,0,12), substr($raw,12,16));
        $job = $text === false ? null : json_decode($text, true);
        if (!is_array($job) || ($job['id'] ?? null) !== $id) throw new \RuntimeException('Backup record cannot be authenticated.');
        return $job;
    }

    private function loadJobs()
    {
        if ($this->jobs !== null) return;
        $this->jobs = []; $this->unknown = false;
        foreach (glob($this->data.'/*.job') ?: [] as $path) {
            try { $id = basename($path, '.job'); $this->jobs[$id] = array_intersect_key($this->readJob($id),array_flip(['id','kind','state','phase','created_at','purpose','archive','safety_id'])); }
            catch (\Throwable $e) { $this->unknown = true; }
        }
    }

    private function reason($id, $allowCompleted = false)
    {
        $j = $this->jobs[$id];
        if (!in_array($j['state'] ?? '', $allowCompleted ? ['failed','cancelled','complete'] : ['failed','cancelled'], true)) return 'Running/queued operations are protected. Completed backups need the new Delete selected confirmation.';
        if (!in_array($j['kind'] ?? '', ['backup','inspect','check'], true)) return 'Restore records and recovery data are protected.';
        if (($j['purpose'] ?? '') === 'restore_safety') return 'Restore safety backup is protected.';
        if ($this->unknown) return 'An unreadable job record exists; cleanup is blocked until its references can be verified.';
        foreach ($this->jobs as $otherId => $other) {
            if ($otherId === $id) continue;
            if ((in_array($other['state'] ?? '', ['pending','running'], true) || in_array($other['phase'] ?? '', ['writing','recovery_required'], true)) && (($other['safety_id'] ?? null) === $id || ($other['archive'] ?? null) === $id)) return 'Another operation references this backup; it is protected.';
        }
        return null;
    }

    public function listing()
    {
        $this->loadJobs(); $rows = []; $total = 0; $failedBytes = 0;
        foreach ($this->jobs as $id => $job) {
            $zip = $this->data.'/'.$id.'.zip'; $exists = is_file($zip) && !is_link($zip); $bytes = $exists ? filesize($zip) : 0;
            $reason = is_link($zip) ? 'Archive symlinks cannot be removed.' : $this->reason($id, true);
            $aux = is_file($this->data.'/'.$id.'.job') ? filesize($this->data.'/'.$id.'.job') : 0;
            $rows[] = ['id'=>$id,'created_at'=>$job['created_at']??null,'kind'=>$job['kind']??'unknown','state'=>$job['state']??'unknown','phase'=>$job['phase']??'unknown','archive_exists'=>$exists,'archive_bytes'=>$bytes,'record_bytes'=>$aux,'can_delete'=>$reason===null,'blocked_reason'=>$reason];
            $total += $bytes; if ($reason === null) $failedBytes += $bytes + $aux;
        }
        usort($rows, function($a,$b){return strcmp($b['created_at']??'', $a['created_at']??'');});
        $orphans=[];
        foreach (glob($this->data.'/*.zip') ?: [] as $path) if (!isset($this->jobs[basename($path,'.zip')]) && !is_link($path)) $orphans[]=['file'=>basename($path),'bytes'=>filesize($path),'note'=>'No authenticated job record. Hosting review required; no delete button.'];
        return ['rows'=>$rows,'archive_bytes'=>$total,'deletable_bytes'=>$failedBytes,'orphan_archives'=>$orphans,'unreadable_records'=>$this->unknown,'scope'=>'Private full-website backups only; update rollback snapshots are separately protected.'];
    }

    public function deleteSelected($ids) { return $this->deleteFailed($ids, true); }

    public function deleteFailed($ids, $allowCompleted = false)
    {
        if (!is_array($ids) || !$ids || count($ids)>100) throw new \RuntimeException('Select 1–100 eligible backup operations.');
        $ids=array_values(array_unique(array_map([$this,'id'],$ids)));
        $lockPath=$this->data.'/operation.lock';
        if (is_link($lockPath)) throw new \RuntimeException('Unsafe backup lock.');
        $lock=fopen($lockPath,'c+');
        if (!$lock || !flock($lock,LOCK_EX|LOCK_NB)) { if($lock)fclose($lock); throw new \RuntimeException('Backup worker is busy. Stop/wait for the current operation, then retry.'); }
        try {
            $this->jobs=null; $this->loadJobs(); $files=[];
            // Validate the entire selection and every file before deleting any byte.
            foreach ($ids as $id) {
                if (!isset($this->jobs[$id])) throw new \RuntimeException('Selected operation does not exist.');
                $reason=$this->reason($id, $allowCompleted); if($reason!==null)throw new \RuntimeException($reason);
                foreach ([$id.'.zip',$id.'.stop',$id.'.job.tmp',$id.'.job'] as $name) {
                    $path=$this->data.'/'.$name;
                    if(is_link($path))throw new \RuntimeException('Symlink cleanup is blocked.');
                    if(file_exists($path) && (!is_file($path)||!$this->within(realpath($path),$this->data)))throw new \RuntimeException('Unsafe backup file.');
                    if(is_file($path))$files[]=['path'=>$path,'bytes'=>filesize($path)];
                }
                foreach(glob($this->data.'/'.$id.'-*.tmp')?:[]as $path){
                    if(!preg_match('/^'.preg_quote($id,'/').'\-[a-f0-9]{64}\.tmp$/D',basename($path)))continue;
                    if(is_link($path)||!is_file($path)||!$this->within(realpath($path),$this->data))throw new \RuntimeException('Unsafe temporary backup file.');
                    $files[]=['path'=>$path,'bytes'=>filesize($path)];
                }
            }
            $freed=0;
            // Job records are removed last, so a failed unlink leaves a recoverable cleanup entry.
            usort($files,function($a,$b){return (int)(substr($a['path'],-4)==='.job')<=>(int)(substr($b['path'],-4)==='.job');});
            foreach($files as $file){if(!@unlink($file['path']))throw new \RuntimeException('Some files could not be deleted. Refresh the list; hosting permissions/quota may require support.');$freed+=$file['bytes'];}
            $this->jobs=null;
            return ['deleted'=>count($ids),'freed_bytes'=>$freed,'message'=>'Selected backup files and records removed. Website images and database were not changed.'];
        } finally { flock($lock,LOCK_UN); fclose($lock); }
    }
}
