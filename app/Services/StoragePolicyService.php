<?php

namespace App\Services;

class StoragePolicyService
{
    public function evaluate(int $total, int $free, bool $hostStale = false): array
    {
        $used = max(0, $total - $free);
        $percent = $total > 0 ? round($used / $total * 100, 1) : 100.0;
        $reserve = (int) config('simpleview.storage.reserve_bytes');
        $critical = $percent >= config('simpleview.storage.block_percent') || $free <= $reserve || $total <= 0;
        $warning = $hostStale || $percent >= config('simpleview.storage.warning_percent') || $free <= config('simpleview.storage.warning_free_bytes');
        return ['status' => $critical ? 'critical' : ($warning ? ($hostStale ? 'stale' : 'warning') : 'ok'),
            'used_percent' => $percent, 'reserved_bytes' => $reserve, 'writable' => $free > $reserve];
    }

    public function operationAllowed(array $metrics, int $requiredBytes = 0): bool
    {
        return $metrics['status'] !== 'critical'
            && ($metrics['filesystem_free_bytes'] - $requiredBytes) >= $metrics['reserved_bytes'];
    }
}
