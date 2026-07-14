<?php
namespace App\Console\Commands;
use App\Services\StorageReconciliationService; use Illuminate\Console\Command;
class FindOrphansCommand extends Command {protected $signature='simpleview:find-orphans {--json}';protected $description='Localiza archivos huérfanos y registros rotos';public function handle(StorageReconciliationService $r):int{$result=$r->run();$this->line($this->option('json')?json_encode($result,JSON_PRETTY_PRINT):json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));return self::SUCCESS;}}
