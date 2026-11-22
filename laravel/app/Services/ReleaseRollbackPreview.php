<?php
namespace App\Services;

/** Read-only interpretation of authenticated release snapshots; update engine is unchanged. */
class ReleaseRollbackPreview
{
    private static function record($path,$key)
    {
        if(is_link($path)||!is_file($path)||filesize($path)>4194304)throw new \RuntimeException('Snapshot record missing/unsafe.');
        $envelope=json_decode(file_get_contents($path),true);
        if(!is_array($envelope)||!is_string($envelope['payload']??null)||!is_string($envelope['mac']??null)||!hash_equals(hash_hmac('sha256',$envelope['payload'],$key),$envelope['mac']))throw new \RuntimeException('Snapshot signature could not be verified.');
        $record=json_decode($envelope['payload'],true);if(!is_array($record))throw new \RuntimeException('Invalid snapshot record.');return $record;
    }
    private static function path($root,$relative)
    {
        if(is_link($root))throw new \RuntimeException('Snapshot root symlink blocked.');
        if(!is_string($relative)||!preg_match('~^(app|config|routes|resources|public/assets)/[A-Za-z0-9_./-]+$~D',$relative)||strpos($relative,'..')!==false)throw new \RuntimeException('Unsafe snapshot file path.');
        $path=$root;foreach(explode('/',$relative)as $part){$path.='/'.$part;if(is_link($path))throw new \RuntimeException('Snapshot symlink blocked.');}return $path;
    }
    public static function inspect($root,$data,$key,$state,$verify=false)
    {
        $report=['current'=>$state['version'],'snapshots'=>[],'next'=>null,'pending'=>is_file($data.'/pending.json'),'verified'=>$verify,'scope'=>'Code files only. Database, orders, customer data, uploaded images, .env and vendor packages are not rolled back.'];
        $history=array_reverse(array_slice($state['history']??[],-2));$position=0;
        foreach($history as $h){
            $item=['id'=>$h['id']??'','from'=>$h['to']??'','target'=>$h['from']??'','at'=>$h['at']??null,'files'=>[],'restore_count'=>0,'remove_count'=>0,'restore_bytes'=>0,'retained'=>false,'ready'=>false,'step'=>++$position,'issues'=>[]];
            try{
                if(!preg_match('/^[a-f0-9]{32}$/D',$item['id']))throw new \RuntimeException('Invalid snapshot ID.');
                $directory=$data.'/backups/'.$item['id'];if(is_link($data)||is_link($data.'/backups')||is_link($directory))throw new \RuntimeException('Snapshot directory is a symlink.');
                $record=self::record($directory.'/record.json',$key);
                if(($record['id']??null)!==$item['id']||($record['from']??null)!==$item['target']||($record['to']??null)!==$item['from']||!is_array($record['files']??null)||count($record['files'])>200)throw new \RuntimeException('Snapshot metadata does not match history.');
                foreach($record['files']as $entry){
                    if(!array_key_exists('before_sha256',$entry)||!is_string($entry['sha256']??null)||!preg_match('/^[a-f0-9]{64}$/D',$entry['sha256'])||($entry['before_sha256']!==null&&(!is_string($entry['before_sha256'])||!preg_match('/^[a-f0-9]{64}$/D',$entry['before_sha256']))))throw new \RuntimeException('Invalid snapshot hash.');
                    $target=self::path($root,$entry['path']);$bytes=0;$action=$entry['before_sha256']===null?'remove_added_file':'restore_previous_file';
                    if($entry['before_sha256']!==null){
                        $backup=self::path($directory.'/files',$entry['path']);if(!is_file($backup))throw new \RuntimeException('A required rollback file is missing.');$bytes=filesize($backup);$item['restore_count']++;$item['restore_bytes']+=$bytes;
                        if($verify&&$position===1&&!hash_equals($entry['before_sha256'],hash_file('sha256',$backup)))$item['issues'][]='Saved file checksum mismatch: '.$entry['path'];
                    }else $item['remove_count']++;
                    if($verify&&$position===1&&(!is_file($target)||!hash_equals($entry['sha256'],hash_file('sha256',$target))))$item['issues'][]='Current file changed/missing: '.$entry['path'];
                    $item['files'][]=['path'=>$entry['path'],'action'=>$action,'restore_bytes'=>$bytes];
                }
                $item['retained']=true;
                if($position===1&&$item['from']!==$state['version'])$item['issues'][]='Snapshot does not match installed version.';
                if($report['pending'])$item['issues'][]='Interrupted update must be recovered first.';
                $item['ready']=$position===1&&$verify&&!$item['issues'];
            }catch(\Throwable $e){$item['issues'][]=$e->getMessage();}
            if($position===1)$report['next']=$item;$report['snapshots'][]=$item;
        }
        return $report;
    }
    public static function current($state,$verify=false)
    {
        return self::inspect(base_path(),dirname(base_path()).'/footstep-update-data',config('app.key'),$state,$verify);
    }
}
