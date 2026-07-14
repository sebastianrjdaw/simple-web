<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class HealthCheck extends Command
{
    protected $signature = 'simpleview:health-check {--quiet-json}';
    protected $description = 'Comprueba base de datos y directorios persistentes';

    public function handle(): int
    {
        $checks = [];
        try { DB::select('select 1'); $checks['database'] = true; } catch (\Throwable) { $checks['database'] = false; }
        foreach (['database', 'media', 'thumbnails', 'backups', 'logs', 'cache'] as $directory) {
            $path = rtrim(config('simpleview.data_path'), '/').'/'.$directory;
            $checks['directory_'.$directory] = is_dir($path) && is_writable($path);
        }
        $ok = ! in_array(false, $checks, true);
        $this->line(json_encode(['ok' => $ok, 'checks' => $checks], JSON_PRETTY_PRINT));
        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
