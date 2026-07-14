<?php
namespace App\Console\Commands;
use App\Services\StorageMetricsService; use App\Services\StorageReconciliationService; use Illuminate\Console\Command;
class StorageReconcileCommand extends Command {protected $signature='simpleview:storage-reconcile {--json}';protected $description='Compara registros y archivos físicos sin borrar nada';public function handle(StorageReconciliationService $r,StorageMetricsService $m):int{$result=$r->run();$m->measure();$this->line($this->option('json')?json_encode($result):sprintf('Huérfanos: %d; registros sin archivo: %d; miniaturas huérfanas: %d',count($result['orphan_media']),count($result['missing_media']),count($result['orphan_thumbnails'])));return self::SUCCESS;}}
