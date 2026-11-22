<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Controllers\WebsiteBackupController;
class WebsiteBackupWork extends Command {
 protected $signature='website-backup:work {--max-time=50}';
 protected $description='Run encrypted website backup, validation, checkup and confirmed restore jobs.';
 public function handle(){try{$result=WebsiteBackupController::service()->work((int)$this->option('max-time'));if(is_array($result)&&($result['state']??null)==='failed'){$this->error('Backup job '.$result['id'].' failed: '.($result['error_code']??'backup_operation_failed').'. Open Admin Progress/results for stage and file details.');return 1;}$this->info('Website backup worker cycle complete.');return 0;}catch(\Throwable $e){\App\Services\BackupDiagnostics::record('worker_initialization_failed',500,bin2hex(random_bytes(8)),'website-backup.worker',$e);$this->error('Backup worker could not initialize. Check private folder permissions and database connection.');return 1;}}
}
