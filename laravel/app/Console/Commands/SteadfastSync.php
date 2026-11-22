<?php
namespace App\Console\Commands;
use App\Services\{StoreOperations,SteadfastDiagnostics,SteadfastAccounts};
class SteadfastSync extends \Illuminate\Console\Command {
 protected $signature='steadfast:sync {--limit=30}';
 protected $description='Sync existing parcels in each labeled Steadfast account; never create bookings.';
 public function handle(){
  $accounts=SteadfastAccounts::listing();$cursor=(int)(StoreOperations::get('steadfast_cron_account_cursor')['index']??0);$deadline=microtime(true)+40;$visited=0;$failed=false;
  while($visited<count($accounts)&&microtime(true)<$deadline){$index=($cursor+$visited)%count($accounts);$id=$accounts[$index]['id'];$visited++;
   try{SteadfastAccounts::using($id,function()use($deadline){$s=StoreOperations::settings();$ready=$s['active']&&$s['mode']==='live'&&!empty($s['api_key'])&&!empty($s['secret_key']);StoreOperations::put('steadfast_worker_health',['at'=>gmdate('c'),'mode'=>$s['mode'],'ready'=>$ready]);if($ready)SteadfastDiagnostics::synchronize('cron',min(5,max(1,(int)$this->option('limit'))),max(5,min(15,(int)($deadline-microtime(true)))));});}
   catch(\Throwable $e){$failed=true;$this->warn('An account sync failed. Review its labeled settings; credentials not logged.');}
  }
  StoreOperations::put('steadfast_cron_account_cursor',['index'=>($cursor+$visited)%max(1,count($accounts))]);return $failed?1:0;
 }
}
