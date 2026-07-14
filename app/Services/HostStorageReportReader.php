<?php

namespace App\Services;

use Carbon\CarbonImmutable;

class HostStorageReportReader
{
    public function read(): ?array
    {
        $path = config('simpleview.storage.host_report_path');
        if (!is_file($path) || is_link($path) || filesize($path) > 1024 * 1024) return null;
        $data = json_decode((string) file_get_contents($path), true);
        if (!is_array($data) || !isset($data['measured_at'])) return null;
        try { $measured = CarbonImmutable::parse($data['measured_at']); } catch (\Throwable) { return null; }
        $numeric = fn ($value) => is_numeric($value) ? max(0, (int) $value) : null;
        return [
            'measured_at' => $measured,
            'stale' => $measured->lt(now()->subMinutes(config('simpleview.storage.host_report_max_age_minutes'))),
            'filesystem_total_bytes' => $numeric($data['filesystem']['total_bytes'] ?? null),
            'filesystem_used_bytes' => $numeric($data['filesystem']['used_bytes'] ?? null),
            'filesystem_free_bytes' => $numeric($data['filesystem']['free_bytes'] ?? null),
            'project_bytes' => $numeric($data['project_bytes'] ?? null),
            'docker_bytes' => $numeric($data['docker']['total_bytes'] ?? null),
            'docker_reclaimable_bytes' => $numeric($data['docker']['reclaimable_bytes'] ?? null),
        ];
    }
}
