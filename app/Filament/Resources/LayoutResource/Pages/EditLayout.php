<?php
namespace App\Filament\Resources\LayoutResource\Pages; use App\Filament\Resources\LayoutResource; use Filament\Resources\Pages\EditRecord;
class EditLayout extends EditRecord {protected static string $resource=LayoutResource::class;public function mount(int|string $record):void{parent::mount($record);$this->redirect(route('visual-editor',$this->record));}}
