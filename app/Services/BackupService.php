<?php
namespace App\Services;
use App\Models\Backup; use App\Models\MediaAsset; use Illuminate\Support\Facades\Process; use Illuminate\Support\Str;
class BackupService {
 public function __construct(private StorageMetricsService $metrics,private StoragePolicyService $policy){}
 public function create(bool $full=false):Backup {
  $estimate=(int)@filesize(config('database.connections.sqlite.database'))+(int)@filesize(base_path('.env'));
  if($full)$estimate+=(int)MediaAsset::sum('file_size')+(int)app(StorageMetricsService::class)->directorySize('/data/thumbnails');
  // Compression is unknown, so reserve the complete source estimate plus 10%.
  $current=$this->metrics->current(true);
  if(!$this->policy->operationAllowed($current,(int)ceil($estimate*1.1)))throw new \RuntimeException($full?'No hay espacio seguro para un backup completo local. Usa una unidad externa, NAS o libera espacio.':'No hay espacio seguro para crear el backup de configuración.');
  $type=$full?'full':'configuration'; $name='simple-view-'.$type.'-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.tar.gz';
  $record=Backup::create(['filename'=>$name,'path'=>$name,'type'=>$type,'status'=>'processing','started_at'=>now()]);
  try {
   $tmp='/tmp/simpleview-backup-'.Str::random(8); mkdir($tmp,0700,true); $db=config('database.connections.sqlite.database');
   $r=Process::run(['sqlite3',$db,".backup '{$tmp}/database.sqlite'"]); if(!$r->successful())throw new \RuntimeException($r->errorOutput());
   copy(base_path('.env'),$tmp.'/.env'); $archive='/data/backups/'.$name;
   $args=['tar','-czf',$archive,'-C',$tmp,'.']; if($full)$args=['tar','-czf',$archive,'-C',$tmp,'.','-C','/data','media','thumbnails'];
   $r=Process::timeout(3600)->run($args); if(!$r->successful())throw new \RuntimeException($r->errorOutput());
   file_put_contents($archive.'.sha256',hash_file('sha256',$archive).'  '.$name.PHP_EOL);
   $record->update(['status'=>'completed','size'=>filesize($archive),'completed_at'=>now()]); Process::run(['rm','-rf',$tmp]); $this->cleanup();
  } catch(\Throwable $e){$record->update(['status'=>'error','error_message'=>$e->getMessage(),'completed_at'=>now()]);throw $e;}
  return $record;
 }
 private function cleanup():void { $keep=(int)env('SIMPLEVIEW_BACKUP_RETENTION_DAYS',7); Backup::where('type','configuration')->where('completed_at','<',now()->subDays($keep))->get()->each(function(Backup $b){@unlink('/data/backups/'.$b->path);@unlink('/data/backups/'.$b->path.'.sha256');$b->delete();}); }
}
