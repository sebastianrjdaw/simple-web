<?php

namespace App\Filament\Widgets;

use App\Models\DisplayStatus;
use App\Models\Layout;
use App\Models\MediaAsset;
use App\Services\BackupScheduleService;
use App\Services\StorageMetricsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatus extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $d = DisplayStatus::where('display_key', 'main-display')->first();
        $online = $d?->last_seen_at?->gt(now()->subSeconds(15));
        $used = MediaAsset::where('status', 'ready')->sum('file_size');
        $processing = MediaAsset::where('status', 'processing')->count();
        try {
            $m = app(StorageMetricsService::class)->current();
            $storage = $m['used_percent'].' % usado';
            $color = $m['status'] === 'critical' ? 'danger' : ($m['status'] === 'ok' ? 'success' : 'warning');
        } catch (\Throwable) {
            $storage = 'Sin medición';
            $color = 'gray';
        }$backup = app(BackupScheduleService::class);
        $last = $backup->lastSuccessful();
        $backupLabel = $last ? $last->completed_at->format('d/m H:i') : 'Sin copias';

        return [Stat::make('Reproductor', $online ? ($d->state === 'online' ? 'En línea' : $d->state) : 'Sin conexión')->color($online ? 'success' : 'danger'), Stat::make('Publicación activa', Layout::where('state', 'published')->max('version') ?: 'Sin publicar')->url(route('filament.admin.resources.layouts.index')), Stat::make('Contenidos', MediaAsset::where('status', 'ready')->count())->description($processing ? "{$processing} procesando · ".number_format($used / 1073741824, 2).' GB' : number_format($used / 1073741824, 2).' GB utilizados')->color($processing ? 'info' : 'gray')->url(route('filament.admin.resources.media-assets.index')), Stat::make('Almacenamiento', $storage)->description('Gestionar almacenamiento')->color($color)->url(route('storage.index')), Stat::make('Copias de seguridad', $backupLabel)->description('Próxima: '.$backup->nextRun()->format('d/m H:i'))->color($backup->isCritical() ? 'danger' : 'success')->url(route('filament.admin.resources.backups.index'))];
    }
}
