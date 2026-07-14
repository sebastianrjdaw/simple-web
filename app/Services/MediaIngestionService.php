<?php
namespace App\Services;
use App\Models\MediaAsset; use Illuminate\Http\UploadedFile; use Illuminate\Support\Facades\Storage;
class MediaIngestionService {
 public function __construct(private UploadCapacityService $capacity,private MediaInspector $inspector,private StorageMetricsService $metrics){}
 public function ingest(UploadedFile $file):MediaAsset {
  $this->capacity->ensure((int)$file->getSize());$path=$file->store('', 'media');
  try{$info=$this->inspector->inspect($path,$file->getClientOriginalName());if($existing=MediaAsset::where('sha256',$info['sha256'])->first()){Storage::disk('media')->delete($path);return $existing;}$record=MediaAsset::create(array_merge($info,['storage_path'=>$path,'original_filename'=>$file->getClientOriginalName(),'display_name'=>pathinfo($file->getClientOriginalName(),PATHINFO_FILENAME),'storage_verified_at'=>now()]));$record->update(['thumbnail_path'=>$this->inspector->thumbnail($path,$record->media_type,$record->sha256)]);$this->metrics->measure();return $record;}catch(\Throwable $e){Storage::disk('media')->delete($path);throw $e;}
 }
}
