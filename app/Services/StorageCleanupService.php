<?php

namespace App\Services;

use App\Models\Backup;
use Illuminate\Support\Facades\Log;

class StorageCleanupService
{
    public function __construct(private StorageReconciliationService $reconciliation) {}

    public function cleanup(array $options, bool $execute = false): array
    {
        $files = [];
        if ($options['temp'] ?? false) $files = array_merge($files, $this->olderFiles('/data/temp', now()->subHours(config('simpleview.storage.temp_retention_hours'))->timestamp));
        if ($options['logs'] ?? false) $files = array_merge($files, $this->olderFiles(storage_path('logs'), now()->subDays(config('simpleview.storage.log_retention_days'))->timestamp));
        if ($options['orphan_thumbnails'] ?? false) foreach ($this->reconciliation->run()['orphan_thumbnails'] as $path) $files[] = '/data/thumbnails/'.$path;
        if ($options['expired_backups'] ?? false) {
            $latest = Backup::where('status', 'completed')->latest('completed_at')->value('id');
            foreach (Backup::where('status', 'completed')->whereKeyNot($latest)->where('completed_at', '<', now()->subDays((int) env('SIMPLEVIEW_BACKUP_RETENTION_DAYS', 7)))->get() as $backup) {
                $files[] = '/data/backups/'.$backup->path;
                $files[] = '/data/backups/'.$backup->path.'.sha256';
                if ($execute) $backup->delete();
            }
        }
        $files = array_values(array_unique(array_filter($files, fn ($path) => str_starts_with($path, '/data/') || str_starts_with($path, storage_path()))));
        $bytes = array_sum(array_map(fn ($path) => is_file($path) ? filesize($path) : 0, $files));
        if ($execute) foreach ($files as $path) if (is_file($path) && !is_link($path)) @unlink($path);
        Log::info('Storage cleanup', ['executed' => $execute, 'files' => count($files), 'bytes' => $bytes]);
        return ['executed' => $execute, 'files' => $files, 'bytes' => $bytes];
    }

    private function olderFiles(string $directory, int $before): array
    {
        if (!is_dir($directory) || is_link($directory)) return [];
        $result = [];
        foreach (new \DirectoryIterator($directory) as $file) if ($file->isFile() && !$file->isLink() && $file->getMTime() < $before) $result[] = $file->getPathname();
        return $result;
    }
}
