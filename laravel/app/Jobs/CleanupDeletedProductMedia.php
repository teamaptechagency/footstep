<?php
namespace App\Jobs;
use Illuminate\Bus\Queueable;use Illuminate\Contracts\Queue\ShouldQueue;use Illuminate\Foundation\Bus\Dispatchable;use Illuminate\Queue\{InteractsWithQueue,SerializesModels};
class CleanupDeletedProductMedia implements ShouldQueue {
 use Dispatchable,InteractsWithQueue,Queueable,SerializesModels;
 public $ids;public $tries=3;public $timeout=240;
 public function __construct(array $ids){$this->ids=$ids;}
 public function handle(){\App\Services\ProductMediaCleanup::drain(50,$this->ids);}
}
