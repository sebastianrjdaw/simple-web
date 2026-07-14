<?php
namespace App\Filament\Resources\MediaAssetResource\Pages;
use App\Filament\Resources\MediaAssetResource; use App\Models\MediaAsset; use App\Services\MediaInspector; use App\Services\StorageMetricsService; use App\Services\UploadCapacityService; use Filament\Resources\Pages\CreateRecord; use Illuminate\Database\Eloquent\Model; use Illuminate\Support\Facades\Storage;
class CreateMediaAsset extends CreateRecord {
    protected static string $resource=MediaAssetResource::class;
    protected function handleRecordCreation(array $data): Model {
        $paths=(array)$data['storage_path'];$names=(array)$data['original_filename'];$first=null;
        try { app(UploadCapacityService::class)->ensure(array_sum(array_map(fn($p)=>Storage::disk('media')->size($p),$paths)),true); }
        catch(\Throwable $e){Storage::disk('media')->delete($paths);throw $e;}
        foreach($paths as $key=>$path){$original=$names[$key]??$names[$path]??basename($path);$info=app(MediaInspector::class)->inspect($path,$original);
          if($existing=MediaAsset::where('sha256',$info['sha256'])->first()){Storage::disk('media')->delete($path);$first??=$existing;continue;}
          $record=MediaAsset::create(array_merge($info,['storage_path'=>$path,'original_filename'=>$original,'display_name'=>pathinfo($original,PATHINFO_FILENAME)]));
          $record->update(['thumbnail_path'=>app(MediaInspector::class)->thumbnail($record->storage_path,$record->media_type,$record->sha256)]);$first??=$record;
        } app(StorageMetricsService::class)->measure(); return $first;
    }
}
