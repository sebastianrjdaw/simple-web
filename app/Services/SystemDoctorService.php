<?php

namespace App\Services;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SystemDoctorService
{
    public function __construct(
        private Migrator $migrator,
        private StorageMetricsService $storageMetrics,
    ) {}

    public function run(bool $repair = false): array
    {
        return Cache::lock('simpleview-system-doctor', 120)->block(2, function () use ($repair) {
            $started = microtime(true);
            $checks = [];

            if ($repair) {
                $this->attempt($checks, 'cache', 'Cachés de Laravel', function () {
                    Artisan::call('optimize:clear');

                    return 'Cachés de configuración, rutas y vistas regenerables limpiadas.';
                }, true);
            }

            $this->attempt($checks, 'database', 'Conexión con la base de datos', function () {
                DB::select('select 1');

                return 'SQLite responde correctamente.';
            });

            $this->attempt($checks, 'sqlite_integrity', 'Integridad de SQLite', function () {
                $row = (array) (DB::selectOne('PRAGMA quick_check') ?? []);
                $result = (string) (reset($row) ?: 'sin respuesta');
                if ($result !== 'ok') {
                    throw new \RuntimeException('PRAGMA quick_check: '.$result);
                }

                return 'PRAGMA quick_check: ok.';
            });

            $this->checkMigrations($checks, $repair);
            $this->checkDirectories($checks, $repair);

            $this->attempt($checks, 'storage', 'Capacidad de almacenamiento', function () {
                $metrics = $this->storageMetrics->current(true);
                if ($metrics['status'] === 'critical') {
                    throw new \RuntimeException('Almacenamiento en estado crítico ('.$metrics['used_percent'].' % usado).');
                }

                return ucfirst($metrics['status']).': '.$metrics['used_percent'].' % usado; '.
                    number_format($metrics['filesystem_free_bytes'] / 1073741824, 1, ',', '.').' GB libres.';
            });

            $this->attempt($checks, 'failed_jobs', 'Cola de trabajos', function () {
                if (! Schema::hasTable('failed_jobs')) {
                    return 'La tabla de trabajos fallidos todavía no existe.';
                }
                $failed = DB::table('failed_jobs')->count();
                if ($failed > 0) {
                    throw new DoctorWarning("Hay {$failed} trabajo(s) fallido(s); requieren revisión manual antes de reintentarlos.");
                }

                return 'No hay trabajos fallidos.';
            });

            $errors = collect($checks)->where('status', 'error')->count();
            $warnings = collect($checks)->where('status', 'warning')->count();
            $repairs = collect($checks)->where('repaired', true)->count();
            $result = [
                'ok' => $errors === 0,
                'mode' => $repair ? 'repair' : 'check',
                'checked_at' => now()->toIso8601String(),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'summary' => ['checks' => count($checks), 'errors' => $errors, 'warnings' => $warnings, 'repairs' => $repairs],
                'checks' => $checks,
            ];
            $this->writeReport($result);

            return $result;
        });
    }

    private function checkMigrations(array &$checks, bool $repair): void
    {
        $this->attempt($checks, 'migrations', 'Migraciones de base de datos', function () use ($repair) {
            $pending = $this->pendingMigrations();
            $repaired = false;
            if ($pending && $repair) {
                Artisan::call('migrate', ['--force' => true]);
                $pending = $this->pendingMigrations();
                $repaired = true;
            }
            if ($pending) {
                throw new \RuntimeException(count($pending).' migración(es) pendiente(s): '.implode(', ', $pending));
            }

            return ['Todas las migraciones están aplicadas.', $repaired];
        });
    }

    private function pendingMigrations(): array
    {
        $files = $this->migrator->getMigrationFiles(database_path('migrations'));
        $ran = $this->migrator->getRepository()->getRan();

        return array_values(array_diff(array_keys($files), $ran));
    }

    private function checkDirectories(array &$checks, bool $repair): void
    {
        $data = rtrim((string) config('simpleview.data_path'), '/');
        $paths = [
            'database' => $data.'/database', 'media' => $data.'/media',
            'thumbnails' => $data.'/thumbnails', 'backups' => $data.'/backups',
            'logs' => $data.'/logs', 'cache' => $data.'/cache',
            'metrics' => $data.'/metrics', 'temp' => $data.'/temp',
            'framework_cache' => storage_path('framework/cache/data'),
            'framework_sessions' => storage_path('framework/sessions'),
            'framework_views' => storage_path('framework/views'),
            'bootstrap_cache' => base_path('bootstrap/cache'),
        ];

        foreach ($paths as $key => $path) {
            $this->attempt($checks, 'directory_'.$key, 'Directorio '.$key, function () use ($path, $repair) {
                $repaired = false;
                if (! is_dir($path) && $repair) {
                    File::ensureDirectoryExists($path, 0770, true);
                    $repaired = true;
                }
                if (! is_dir($path) || ! is_writable($path)) {
                    throw new \RuntimeException("No existe o no permite escritura: {$path}");
                }
                $probe = $path.'/.doctor-'.Str::random(10);
                if (@file_put_contents($probe, 'ok') === false) {
                    throw new \RuntimeException("No se pudo escribir en {$path}");
                }
                @unlink($probe);

                return [$repaired ? 'Directorio creado y escritura verificada.' : 'Escritura verificada.', $repaired];
            }, false);
        }
    }

    private function attempt(array &$checks, string $key, string $label, callable $callback, bool $markRepair = false): void
    {
        try {
            $outcome = $callback();
            [$detail, $repaired] = is_array($outcome) ? $outcome : [$outcome, $markRepair];
            $checks[] = compact('key', 'label') + ['status' => 'ok', 'detail' => $detail, 'repaired' => (bool) $repaired];
        } catch (DoctorWarning $e) {
            $checks[] = compact('key', 'label') + ['status' => 'warning', 'detail' => $e->getMessage(), 'repaired' => false];
        } catch (\Throwable $e) {
            $checks[] = compact('key', 'label') + ['status' => 'error', 'detail' => $e->getMessage(), 'repaired' => false];
        }
    }

    private function writeReport(array $result): void
    {
        $path = rtrim((string) config('simpleview.data_path'), '/').'/logs/system-doctor.log';
        if (! is_dir(dirname($path)) || ! is_writable(dirname($path))) {
            return;
        }
        @file_put_contents($path, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}

class DoctorWarning extends \RuntimeException {}
