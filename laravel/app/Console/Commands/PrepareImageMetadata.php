<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Upload;
use App\Services\OptimizedImages;
class PrepareImageMetadata extends Command
{
    protected $signature = 'images:prepare-metadata {--after=0 : Resume after upload ID} {--limit=100 : Maximum uploads per run} {--continue : Resume background cursor; revisit existing records daily}';
    protected $description = 'Prepare image URL manifests from existing images/variants; no resizing or conversion.';
    public function handle()
    {
        $after = max(0, (int) $this->option('after'));
        $limit = max(1, min(500, (int) $this->option('limit')));
        $cursor = storage_path('app/image-manifests/cursor.json');
        if ($this->option('continue') && is_file($cursor)) {
            $state = json_decode(file_get_contents($cursor), true) ?: [];
            if (($state['completed_at'] ?? 0) > time() - 86400) {
                $this->info('Metadata sweep completed; next full refresh within 24 hours. New uploads prepare their own manifests.');
                return 0;
            }
            $after = max($after, (int) ($state['after'] ?? 0));
        }
        $uploads = Upload::where('type', 'image')->where('id', '>', $after)->orderBy('id')->limit($limit)->get();
        $ok = 0;
        $processed = 0;
        $deadline = microtime(true) + 20;
        foreach ($uploads as $u) {
            if (microtime(true) > $deadline) {
                break;
            }
            if (OptimizedImages::prepareMetadata($u->file_name)) {
                $ok++;
            }
            $after = $u->id;
            $processed++;
        }
        if ($this->option('continue')) {
            $done = !Upload::where('type', 'image')->where('id', '>', $after)->exists();
            if (!is_dir(dirname($cursor))) {
                mkdir(dirname($cursor), 0755, true);
            }
            $temp = $cursor . '.' . bin2hex(random_bytes(4));
            file_put_contents($temp, json_encode(['after' => $done ? 0 : $after, 'completed_at' => $done ? time() : null]), LOCK_EX);
            if (!rename($temp, $cursor)) {
                throw new \RuntimeException('Metadata cursor could not be saved.');
            }
        }
        $this->info('Prepared ' . $ok . ' / ' . $processed . ' image manifests. Resume: --after=' . $after);
        return 0;
    }
}
