<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
class RecordQueueHeartbeat implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public $tries=1;
 public function handle(){
  $p=storage_path('app/queue-worker-heartbeat.json');$tmp=$p.'.'.bin2hex(random_bytes(4)).'.tmp';
  if(file_put_contents($tmp,json_encode(['consumed_at'=>gmdate('c'),'driver'=>config('queue.default')]),LOCK_EX)===false||!rename($tmp,$p))throw new \RuntimeException('Queue heartbeat write failed.');
  chmod($p,0640);
 }
}
