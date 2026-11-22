<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\Inbox\Processor;
class InboxWork extends Command {
 protected $signature='inbox:work {--once} {--stop-when-empty} {--max-time=50} {--business=}';
 protected $description='Process isolated Inbox database queue; safe for cron or supervised workers.';
 public function handle(){ $this->info('Inbox has been removed; worker stopped.'); return 0; }
 private function retiredHandle(){
  $until=microtime(true)+max(1,min(3600,(int)$this->option('max-time')));
  do{DB::table('inbox_businesses')->when($this->option('business'),fn($q)=>$q->where('id',(int)$this->option('business')))->update(['last_worker_at'=>now()]);$worked=Processor::tick($this->option('business')?(int)$this->option('business'):null);if($this->option('once')||(!$worked&&$this->option('stop-when-empty')))break;if(!$worked)usleep(500000);}while(microtime(true)<$until);
  $this->info('Inbox worker cycle complete.');return 0;
 }
}
