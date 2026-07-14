<?php
namespace App\Filament\Resources\MediaAssetResource\Pages;
use App\Filament\Resources\MediaAssetResource; use Filament\Actions; use Filament\Resources\Pages\EditRecord;
class EditMediaAsset extends EditRecord { protected static string $resource=MediaAssetResource::class; protected function getHeaderActions():array{return[Actions\DeleteAction::make()->visible(fn()=>!$this->record->in_use)];} protected function mutateFormDataBeforeSave(array $data):array{unset($data['storage_path'],$data['original_filename']);return $data;} }
