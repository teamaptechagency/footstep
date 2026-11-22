<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Http\Controllers\WebsiteBackupController;
class WebsiteBackupRecover extends Command {
 protected $signature='website-backup:recover {id} {--scope=both}';
 protected $description='Recover an interrupted restore using a verified safety backup; prompts for archive password.';
 public function handle(){if(!$this->confirm('This restores the selected safety backup and can replace current files/database. Continue?',false))return 1;$password=$this->secret('Archive password (never pass it on the command line)');try{WebsiteBackupController::service()->recover($this->argument('id'),$password,$this->option('scope'));$this->info('Safety recovery complete; maintenance mode cleared.');return 0;}catch(\Throwable $e){$this->error('Recovery did not complete. Maintenance was not automatically cleared. Check private backup files, password and database permissions.');return 1;}}
}
