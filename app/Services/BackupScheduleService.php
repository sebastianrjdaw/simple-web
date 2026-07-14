<?php

namespace App\Services;

use App\Models\Backup;
use App\Models\Setting;
use Illuminate\Support\Carbon;

class BackupScheduleService
{
    public function frequencyDays(): int
    {
        return min(2, max(1, (int) Setting::valueOf('backup_frequency_days', 2)));
    }

    public function scheduledTime(): string
    {
        $value = (string) Setting::valueOf('backup_time', '03:00');
        return preg_match('/^\d{2}:\d{2}$/', $value) ? $value : '03:00';
    }

    public function typeIsFull(): bool
    {
        return Setting::valueOf('backup_type', 'configuration') === 'full';
    }

    public function lastSuccessful(): ?Backup
    {
        return Backup::where('status', 'completed')->latest('completed_at')->first();
    }

    public function nextRun(?Carbon $from = null): Carbon
    {
        $from ??= now();
        $last = $this->lastSuccessful();
        [$hour, $minute] = array_map('intval', explode(':', $this->scheduledTime()));
        $base = $last?->completed_at?->copy()->timezone(config('app.timezone')) ?? $from->copy()->subDays($this->frequencyDays());
        $next = $base->copy()->startOfDay()->addDays($this->frequencyDays())->setTime($hour, $minute);

        while ($next->lte($from) && $last && $last->completed_at?->gt($next)) {
            $next->addDays($this->frequencyDays());
        }

        return $next;
    }

    public function due(?Carbon $now = null): bool
    {
        $now ??= now();
        if ((string) Setting::valueOf('backup_automatic_enabled', '1') !== '1') {
            return false;
        }

        if (Backup::where('status', 'processing')->where('started_at', '>', $now->copy()->subHours(6))->exists()) {
            return false;
        }

        $last = $this->lastSuccessful();
        if (!$last) {
            return $now->format('H:i') >= $this->scheduledTime();
        }

        if ($last->completed_at->lte($now->copy()->subHours(48))) {
            return true;
        }

        return $this->nextRun($now)->lte($now);
    }

    public function isCritical(?Carbon $now = null): bool
    {
        $now ??= now();
        $last = $this->lastSuccessful();
        return !$last || $last->completed_at->lte($now->copy()->subHours(48));
    }
}
