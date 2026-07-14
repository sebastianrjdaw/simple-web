<?php

namespace App\Services;

use App\Models\MediaAsset;
use App\Models\StorageSnapshot;
use Illuminate\Support\Facades\Cache;

class StorageMetricsService
{
    public function __construct(private HostStorageReportReader $host, private StoragePolicyService $policy) {}

    public function current(bool $refresh = false): array
    {
        if (!$refresh && ($latest = StorageSnapshot::latest('source_measured_at')->first())
            && $latest->source_measured_at->gt(now()->subMinutes(config('simpleview.storage.scan_interval_minutes')))) {
            return $this->snapshotArray($latest);
        }
        return $this->measure();
    }

    public function measure(): array
    {
        return Cache::lock('simpleview-storage-scan', 120)->block(5, function () {
            $root = config('simpleview.storage.data_path');
            if (!is_dir($root)) $root = storage_path();
            $total = (int) @disk_total_space($root);
            $free = (int) @disk_free_space($root);
            $host = $this->host->read();
            $total = $host['filesystem_total_bytes'] ?? $total;
            $free = $host['filesystem_free_bytes'] ?? $free;
            $policy = $this->policy->evaluate($total, $free, (bool) ($host['stale'] ?? false));
            $parts = [
                'media_bytes' => $this->directorySize("$root/media"),
                'thumbnails_bytes' => $this->directorySize("$root/thumbnails"),
                'database_bytes' => is_file("$root/database/database.sqlite") ? (int) filesize("$root/database/database.sqlite") : 0,
                'backups_bytes' => $this->directorySize("$root/backups"),
                'logs_bytes' => $this->directorySize("$root/logs") + $this->directorySize(storage_path('logs')),
                'cache_bytes' => $this->directorySize(storage_path('framework/cache')),
                'temp_bytes' => $this->directorySize("$root/temp") + $this->directorySize(storage_path('framework/livewire-tmp')),
                'project_bytes' => (int) ($host['project_bytes'] ?? 0),
                'docker_bytes' => $host['docker_bytes'] ?? null,
                'docker_reclaimable_bytes' => $host['docker_reclaimable_bytes'] ?? null,
            ];
            $known = array_sum(array_filter($parts, 'is_int'));
            $data = array_merge([
                'filesystem_total_bytes' => $total, 'filesystem_used_bytes' => max(0, $total - $free),
                'filesystem_free_bytes' => $free, 'reserved_bytes' => $policy['reserved_bytes'],
                'other_bytes' => max(0, ($total - $free) - $known), 'status' => $policy['status'],
                'source_measured_at' => now(), 'details_json' => ['used_percent' => $policy['used_percent'], 'host_report' => $host ? ['measured_at' => $host['measured_at']->toIso8601String(), 'stale' => $host['stale']] : null,
                    'registered_media_bytes' => (int) MediaAsset::sum('file_size')],
            ], $parts);
            $snapshot = StorageSnapshot::create($data);
            StorageSnapshot::where('id', '<>', $snapshot->id)->where('created_at', '<', now()->subDays(30))->delete();
            return $this->snapshotArray($snapshot);
        });
    }

    public function directorySize(string $path): int
    {
        if (!is_dir($path) || is_link($path)) return 0;
        $size = 0;
        try {
            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS));
            foreach ($it as $file) if ($file->isFile() && !$file->isLink()) $size += $file->getSize();
        } catch (\Throwable) {}
        return $size;
    }

    private function snapshotArray(StorageSnapshot $snapshot): array
    {
        $data = $snapshot->toArray();
        $data['used_percent'] = (float) ($snapshot->details_json['used_percent'] ?? 0);
        return $data;
    }
}
