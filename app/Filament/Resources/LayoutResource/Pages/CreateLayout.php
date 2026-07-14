<?php
namespace App\Filament\Resources\LayoutResource\Pages; use App\Filament\Resources\LayoutResource; use Filament\Resources\Pages\CreateRecord;
class CreateLayout extends CreateRecord {protected static string $resource=LayoutResource::class;protected function afterCreate():void{$count=LayoutResource::zoneCount($this->record->template_key);for($i=1;$i<=$count;$i++)$this->record->zones()->create(['zone_key'=>'zone_'.$i,'position'=>$i]);}protected function getRedirectUrl():string{return config('simpleview.visual_editor_enabled')?route('visual-editor',$this->record):parent::getRedirectUrl();}}
