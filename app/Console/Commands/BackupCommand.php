<?php
namespace App\Console\Commands; use App\Services\BackupService; use Illuminate\Console\Command;
class BackupCommand extends Command {protected $signature='simpleview:backup {--full}';protected $description='Crea una copia de seguridad';public function handle():int{$b=app(BackupService::class)->create($this->option('full'));$this->info($b->filename);return self::SUCCESS;}}
