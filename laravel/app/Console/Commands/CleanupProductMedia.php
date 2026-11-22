<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
class CleanupProductMedia extends Command {
 protected $signature='products:cleanup-media {--limit=50}';protected $description='Retry unused media cleanup after product deletion';
 public function handle(){$r=\App\Services\ProductMediaCleanup::drain(max(1,min(100,(int)$this->option('limit'))));$this->line(json_encode($r));return 0;}
}
