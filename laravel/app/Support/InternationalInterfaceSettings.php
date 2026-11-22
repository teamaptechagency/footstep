<?php
namespace App\Support;

final class InternationalInterfaceSettings
{
    private string $directory;
    private string $path;
    public function __construct(?string $directory = null)
    {
        $this->directory = $directory ?? storage_path('app');
        $this->path = $this->directory.'/international-interface.json';
    }
    private function assertSafeDirectory(): void
    {
        if (is_link($this->directory) || !is_dir($this->directory) || fileowner($this->directory) !== posix_geteuid()) {
            throw new \RuntimeException('Unsafe settings directory');
        }
    }
    private function assertSafeFile(): void
    {
        if (is_link($this->path) || !is_file($this->path) || fileowner($this->path) !== posix_geteuid() || (fileperms($this->path) & 0002) || filesize($this->path) > 4096) {
            throw new \RuntimeException('Unsafe settings file');
        }
    }
    public function enabled(): bool
    {
        try {
            clearstatcache(true, $this->path);
            $this->assertSafeDirectory();
            $this->assertSafeFile();
            $data = json_decode(file_get_contents($this->path), true, 8, JSON_THROW_ON_ERROR);
            return is_array($data) && ($data['version'] ?? null) === 1 && ($data['enabled'] ?? null) === true;
        } catch (\Throwable $e) {
            return false;
        }
    }
    public function save(bool $enabled): void
    {
        $this->assertSafeDirectory();
        clearstatcache(true, $this->path);
        if (file_exists($this->path) || is_link($this->path)) $this->assertSafeFile();
        if (!is_writable($this->directory)) throw new \RuntimeException('Cannot prepare settings');
        $temporary = @tempnam($this->directory, '.international-');
        if ($temporary === false) throw new \RuntimeException('Cannot prepare settings');
        if (dirname($temporary) !== realpath($this->directory)) { unlink($temporary); throw new \RuntimeException('Cannot prepare settings'); }
        try {
            $json = json_encode(['version' => 1, 'enabled' => $enabled], JSON_THROW_ON_ERROR)."\n";
            if (file_put_contents($temporary, $json, LOCK_EX) !== strlen($json) || !chmod($temporary, 0640) || !rename($temporary, $this->path)) {
                throw new \RuntimeException('Cannot save settings');
            }
        } finally {
            if (is_file($temporary)) unlink($temporary);
        }
    }
}
