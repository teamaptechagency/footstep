<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Services\OrderSms;

class DispatchOrderSms extends Command
{
    protected $signature = 'sms:dispatch {--limit=20}';
    protected $description = 'Process transactional order SMS outbox (no historical-order backfill)';
    public function handle()
    {
        \Illuminate\Support\Facades\Cache::put('order_sms_worker_seen', now()->toIso8601String(), 86400);
        OrderSms::process(max(1, min(50, (int)$this->option('limit'))));
        $this->info('Order SMS outbox checked.');
        return 0;
    }
}
