<?php
namespace App\Filament\Resources\MediaAssetResource\Pages;
use App\Filament\Resources\MediaAssetResource; use App\Models\MediaAsset; use App\Services\WebEmbedService; use Filament\Actions; use Filament\Forms; use Filament\Notifications\Notification; use Filament\Resources\Pages\ListRecords;
class ListMediaAssets extends ListRecords { protected static string $resource=MediaAssetResource::class; protected function getHeaderActions(): array{return[
 Actions\Action::make('aimharder')->label('WOD de AIMHARDER')->icon('heroicon-o-globe-alt')->form([
  Forms\Components\TextInput::make('name')->label('Nombre visible')->default('WOD del día')->required()->maxLength(120),
  Forms\Components\TextInput::make('url')->label('URL de AIMHARDER')->default(config('simpleview.web_embed.default_url'))->required()->maxLength(2048),
  Forms\Components\Select::make('refresh_interval_minutes')->label('Actualización automática')->options([0=>'Sin recarga periódica',5=>'Cada 5 minutos',15=>'Cada 15 minutos',30=>'Cada 30 minutos',60=>'Cada 60 minutos'])->default(15)->required(),
  Forms\Components\Toggle::make('interaction_enabled')->label('Permitir interacción con la página')->default(false)->helperText('Desactivado evita clics accidentales en la televisión.'),
  Forms\Components\Select::make('scroll_mode')->label('Desplazamiento')->options(['full'=>'Mostrar página completa','auto'=>'Permitir desplazamiento','hidden'=>'Ocultar desplazamiento'])->default('full')->required(),
  Forms\Components\Select::make('fallback_media_asset_id')->label('Imagen de respaldo')->options(fn()=>MediaAsset::where('media_type','image')->where('status','ready')->pluck('display_name','id'))->searchable()->placeholder('Usar respaldo global'),
  ])->action(function(array $data){app(WebEmbedService::class)->create($data);Notification::make()->success()->title('Bloque AIMHARDER creado')->send();}),
 Actions\CreateAction::make()->label('Subir archivos')];} }
