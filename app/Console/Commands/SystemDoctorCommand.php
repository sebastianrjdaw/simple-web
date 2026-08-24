<?php

namespace App\Console\Commands;

use App\Services\SystemDoctorService;
use Illuminate\Console\Command;

class SystemDoctorCommand extends Command
{
    protected $signature = 'simpleview:doctor {--repair : Aplica reparaciones seguras de aplicación} {--json : Devuelve JSON}';
    protected $description = 'Comprueba y repara de forma segura la aplicación Simple View';

    public function handle(SystemDoctorService $doctor): int
    {
        $result = $doctor->run((bool) $this->option('repair'));
        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Comprobación', 'Estado', 'Detalle'], array_map(
                fn (array $check) => [$check['label'], strtoupper($check['status']), $check['detail']],
                $result['checks'],
            ));
            $summary = $result['summary'];
            $this->line("{$summary['checks']} comprobaciones; {$summary['errors']} errores; {$summary['warnings']} avisos; {$summary['repairs']} reparaciones.");
        }

        return $result['ok'] ? self::SUCCESS : self::FAILURE;
    }
}
