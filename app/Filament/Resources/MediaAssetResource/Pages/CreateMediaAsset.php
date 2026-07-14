<?php
namespace App\Filament\Resources\MediaAssetResource\Pages;
use App\Filament\Resources\MediaAssetResource; use App\Models\MediaAsset; use App\Services\MediaInspector; use Filament\Resources\Pages\CreateRecord; use Illuminate\Database\Eloquent\Model; use Illuminate\Support\Facades\Storage;
class CreateMediaAsset extends CreateRecord {
    protected static string $resource=MediaAssetResource::class;
    protected function handleRecordCreation(array $data): Model {
        $paths=(array)$data['storage_path'];$names=(array)$data['original_filename'];$first=null;
        $capacity=disk_total_space('/data');$free=disk_free_space('/data');$usedPercent=$capacity?100-(($free/$capacity)*100):0;
        if($usedPercent >= (int)env('SIMPLEVIEW_STORAGE_BLOCK_PERCENT',90)){Storage::disk('media')->delete($paths);throw \Illuminate\Validation\ValidationException::withMessages(['data.storage_path'=>'No hay espacio suficiente. Elimina contenidos antes de subir más.']);}
        foreach($paths as $key=>$path){$original=$names[$key]??$names[$path]??basename($path);$info=app(MediaInspector::class)->inspect($path,$original);
          if($existing=MediaAsset::where('sha256',$info['sha256'])->first()){Storage::disk('media')->delete($path);$first??=$existing;continue;}
          $record=MediaAsset::create(array_merge($info,['storage_path'=>$path,'original_filename'=>$original,'display_name'=>pathinfo($original,PATHINFO_FILENAME)]));
          $record->update(['thumbnail_path'=>app(MediaInspector::class)->thumbnail($record->storage_path,$record->media_type,$record->sha256)]);$first??=$record;
        } return $first;
    }
}
