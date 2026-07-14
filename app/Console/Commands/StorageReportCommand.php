<?php
namespace App\Console\Commands;
use App\Services\StorageMetricsService;
use Illuminate\Console\Command;
class StorageReportCommand extends Command {
 protected $signature='simpleview:storage-report {--json} {--refresh}'; protected $description='Muestra las métricas y el estado de almacenamiento';
 public function handle(StorageMetricsService $service):int{$m=$service->current((bool)$this->option('refresh'));if($this->option('json')){$this->line(json_encode($m,JSON_UNESCAPED_SLASHES));return self::SUCCESS;}$this->table(['Métrica','Valor'],[['Estado',$m['status']],['Total',$this->size($m['filesystem_total_bytes'])],['Usado',$this->size($m['filesystem_used_bytes']).' ('.$m['used_percent'].' %)'],['Libre',$this->size($m['filesystem_free_bytes'])],['Reserva',$this->size($m['reserved_bytes'])],['Multimedia',$this->size($m['media_bytes'])],['Miniaturas',$this->size($m['thumbnails_bytes'])],['Backups',$this->size($m['backups_bytes'])],['Base de datos',$this->size($m['database_bytes'])],['Docker',$m['docker_bytes']===null?'Sin informe del host':$this->size($m['docker_bytes'])],['Medición',(string)$m['source_measured_at']]]);return $m['status']==='critical'?self::FAILURE:self::SUCCESS;}
 private function size(int $b):string{return number_format($b/1024**3,2,',','.').' GB';}
}
