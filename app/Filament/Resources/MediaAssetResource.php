<?php
namespace App\Filament\Resources;

use App\Filament\Resources\MediaAssetResource\Pages;
use App\Models\MediaAsset;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MediaAssetResource extends Resource {
    protected static ?string $model=MediaAsset::class; protected static ?string $navigationIcon='heroicon-o-photo';
    protected static ?string $navigationLabel='Contenidos'; protected static ?string $modelLabel='contenido'; protected static ?string $pluralModelLabel='contenidos';
    public static function form(Form $form): Form { return $form->schema([
        Forms\Components\FileUpload::make('storage_path')->label('Archivos')->disk('media')->directory('originals')->storeFileNamesIn('original_filename')->multiple()->required()->acceptedFileTypes(['image/jpeg','image/png','image/webp','video/mp4'])->maxSize(self::maxUploadKilobytes())->helperText(self::uploadLimitText())->visibleOn('create'),
        Forms\Components\TextInput::make('display_name')->label('Nombre visible')->maxLength(255)->visibleOn('edit'),
    ]); }
    public static function table(Table $table): Table { return $table->columns([
        Tables\Columns\ImageColumn::make('thumbnail_path')->label('')->disk('thumbnails')->square(),
        Tables\Columns\TextColumn::make('display_name')->label('Nombre')->searchable()->sortable(),
        Tables\Columns\TextColumn::make('media_type')->label('Tipo')->badge()->formatStateUsing(fn($state)=>$state==='image'?'Imagen':'Vídeo'),
        Tables\Columns\TextColumn::make('file_size')->label('Tamaño')->formatStateUsing(fn($state)=>number_format($state/1048576,1).' MB')->sortable(),
        Tables\Columns\TextColumn::make('status')->label('Estado')->badge(),
        Tables\Columns\IconColumn::make('in_use')->label('En uso')->boolean(),
        Tables\Columns\TextColumn::make('created_at')->label('Subido')->dateTime('d/m/Y H:i')->sortable(),
    ])->filters([Tables\Filters\SelectFilter::make('media_type')->label('Tipo')->options(['image'=>'Imágenes','video'=>'Vídeos'])])
      ->actions([Tables\Actions\Action::make('download')->label('Descargar')->icon('heroicon-o-arrow-down-tray')->url(fn(MediaAsset $record)=>route('media.download',$record)),Tables\Actions\EditAction::make(),Tables\Actions\DeleteAction::make()->visible(fn(MediaAsset $record)=>!$record->in_use)->before(function(MediaAsset $record){\Storage::disk('media')->delete($record->storage_path);if($record->thumbnail_path)\Storage::disk('thumbnails')->delete($record->thumbnail_path);})]); }
    public static function getPages(): array { return ['index'=>Pages\ListMediaAssets::route('/'),'create'=>Pages\CreateMediaAsset::route('/create'),'edit'=>Pages\EditMediaAsset::route('/{record}/edit')]; }
    private static function maxUploadKilobytes(): int { $functional=(int)config('simpleview.max_upload_mb');$hard=max(1,(int)config('simpleview.max_upload_hard_mb'));return ($functional<=0? $hard:min($functional,$hard))*1024; }
    private static function uploadLimitText(): string { $functional=(int)config('simpleview.max_upload_mb');$hard=(int)config('simpleview.max_upload_hard_mb');return $functional<=0?"Sin límite funcional. Límite técnico: {$hard} MB por archivo.":'Máximo '.min($functional,$hard).' MB por archivo.'; }
}
