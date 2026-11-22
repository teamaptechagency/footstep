<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [];
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('carrybee:sync')->everyMinute()->withoutOverlapping(5);
        $schedule->command('products:cleanup-media --limit=50')->everyMinute()->withoutOverlapping(10);
        $schedule->command('sms:dispatch --limit=20')->everyMinute()->withoutOverlapping(5);
        $schedule->job(new \App\Jobs\RecordQueueHeartbeat)->everyFiveMinutes()->withoutOverlapping(10);
        $schedule->command('platform:runtime-health')->everyFiveMinutes()->withoutOverlapping(10);
        $schedule->command('images:prepare-metadata --continue --limit=50')->everyFiveMinutes()->withoutOverlapping(10);
        if (config('server_operations.live_integrations', false) || config('server_operations.steadfast_status_sync', false)) {
            $schedule->command('steadfast:sync --limit=30')->everyFiveMinutes()->withoutOverlapping(10);
        }
        if (config('server_operations.live_integrations', false) || config('server_operations.meta_capi_dispatch', false)) {
            $schedule->command('meta:dispatch')->everyMinute()->withoutOverlapping(5);
        }
        // $schedule->command('inspire')
        //          ->hourly();
    }
    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
