<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('simpleview:backup')->everyFifteenMinutes()->withoutOverlapping();
Schedule::command('simpleview:storage-report --refresh')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('simpleview:storage-reconcile')->dailyAt(str_pad((string) env('SIMPLEVIEW_STORAGE_DEEP_SCAN_HOUR', 3), 2, '0', STR_PAD_LEFT).':15')->withoutOverlapping();
Schedule::command('simpleview:cleanup-storage --temp --logs --expired-backups --orphan-thumbnails --force')->dailyAt('04:10')->withoutOverlapping();
