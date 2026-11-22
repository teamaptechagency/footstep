<?php

namespace App\Console\Commands;

use App\Services\RuntimeHealth;
use Illuminate\Console\Command;
class CollectRuntimeHealth extends Command
{
    protected $signature = 'platform:runtime-health';
    protected $description = 'Collect safe queue/runtime counts for Performance Logs; no external API calls.';
    public function handle()
    {
        $health = RuntimeHealth::collect();
        $this->info('Runtime heartbeat saved. Queue: ' . $health['queue'] . '. This is not proof that a queue worker consumed jobs.');
        return 0;
    }
}
