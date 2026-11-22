<?php
namespace App\Services;

class CatalogOperationLog
{
    public static function directory(){return dirname(base_path()).'/footstep-performance-data/catalog';}
    public static function append(array $row)
    {
        $dir=self::directory();if(!is_dir($dir)&&!@mkdir($dir,0700,true))return;
        $lock=@fopen($dir.'/write.lock','c');if(!$lock)return;
        try{
            if(!flock($lock,LOCK_EX))return;
            $p=$dir.'/'.gmdate('Y-m-d').'.jsonl';clearstatcache(true,$p);
            if(is_file($p)&&filesize($p)>1048576)@rename($p,$p.'.previous');
            file_put_contents($p,json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n",FILE_APPEND|LOCK_EX);@chmod($p,0600);
            foreach(glob($dir.'/*.jsonl*')?:[] as $old)if(filemtime($old)<time()-14*86400)@unlink($old);
        }finally{flock($lock,LOCK_UN);fclose($lock);}
    }
    public static function recent()
    {
        $rows=[];$files=glob(self::directory().'/*.jsonl')?:[];rsort($files);
        foreach(array_slice($files,0,3) as $p)foreach(array_reverse(file($p,FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES)) as $line){$r=json_decode($line,true);if(is_array($r))$rows[]=$r;if(count($rows)>=100)return $rows;}
        return $rows;
    }
    public static function explain($error,$status)
    {
        if($error instanceof \Illuminate\Validation\ValidationException)return ['validation_failed','Form-এর কিছু তথ্য গ্রহণযোগ্য নয়।','চিহ্নিত field ঠিক করে আবার Save করুন। আগের data অপরিবর্তিত থাকবে।'];
        if($error instanceof \Illuminate\Database\QueryException)return ['database_write_failed','Database-এ save হয়নি।','Request ID দিয়ে developer-কে জানান; database constraint/schema পরীক্ষা প্রয়োজন।'];
        if($status===419)return ['session_expired','Login/session মেয়াদ শেষ।','Page refresh করে login করুন, তারপর আবার চেষ্টা করুন।'];
        if($status===429)return ['rate_limited','অল্প সময়ে বেশি request।','কিছুক্ষণ অপেক্ষা করে একবার চেষ্টা করুন।'];
        if(in_array($status,[401,403],true))return ['access_denied','এই কাজের অনুমতি নেই।','Admin login ও module/staff permission পরীক্ষা করুন।'];
        if($status>=400)return ['request_failed','কাজটি সম্পন্ন হয়নি।','Request ID দিয়ে report দিন; নতুন করে data delete করবেন না।'];
        return ['completed','Request সম্পন্ন হয়েছে।','তালিকায় পরিবর্তন মিলিয়ে দেখুন। Warning থাকলে page-এর warning অনুসরণ করুন।'];
    }
}
