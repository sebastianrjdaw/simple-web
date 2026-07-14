<?php
namespace App\Filament\Resources;

use App\Filament\Resources\MediaAssetResource\Pages;
use App\Models\MediaAsset;
use App\Services\MediaDeletionService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
        Tables\Columns\TextColumn::make('last_used_at')->label('Último uso')->dateTime('d/m/Y H:i')->sortable()->placeholder('Nunca'),
        Tables\Columns\TextColumn::make('created_at')->label('Subido')->dateTime('d/m/Y H:i')->sortable(),
    ])->filters([
        Tables\Filters\SelectFilter::make('media_type')->label('Tipo')->options(['image'=>'Imágenes','video'=>'Vídeos']),
        Tables\Filters\SelectFilter::make('status')->label('Estado')->options(['ready'=>'Listo','processing'=>'Procesando','error'=>'Con error']),
        Tables\Filters\Filter::make('unused')->label('Sin uso')->query(fn(Builder $query)=>$query->doesntHave('playlistItems')),
        Tables\Filters\Filter::make('large')->label('Más de 500 MB')->query(fn(Builder $query)=>$query->where('file_size','>=',500*1024*1024)),
        Tables\Filters\Filter::make('never_used')->label('Nunca utilizados')->query(fn(Builder $query)=>$query->whereNull('last_used_at')),
    ])
      ->actions([
        Tables\Actions\Action::make('download')->label('Descargar')->icon('heroicon-o-arrow-down-tray')->url(fn(MediaAsset $record)=>route('media.download',$record)),
        Tables\Actions\Action::make('uses')->label('Ver usos')->icon('heroicon-o-map')->modalHeading(fn(MediaAsset $record)=>'Usos de '.$record->display_name)->modalSubmitAction(false)->modalCancelActionLabel('Cerrar')->modalContent(fn(MediaAsset $record)=>view('filament.media-uses',['asset'=>$record,'classification'=>app(MediaDeletionService::class)->classify($record)])),
        Tables\Actions\EditAction::make(),
        Tables\Actions\Action::make('delete_safe')
            ->label('Eliminar')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(fn(MediaAsset $record)=>'Eliminar '.$record->display_name)
            ->modalDescription(fn(MediaAsset $record)=>app(MediaDeletionService::class)->classify($record)['message'])
            ->action(function(MediaAsset $record){
                $result=app(MediaDeletionService::class)->delete($record,(int)auth()->id(),false);
                Notification::make()
                    ->title($result['deleted']?'Contenido eliminado':'No se pudo eliminar')
                    ->body($result['message'] ?? $result['error'] ?? '')
                    ->{$result['deleted']?'success':'warning'}()
                    ->send();
            }),
        Tables\Actions\Action::make('detach_delete')
            ->label('Retirar y eliminar')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn(MediaAsset $record)=>app(MediaDeletionService::class)->classify($record)['can_detach_and_delete'])
            ->requiresConfirmation()
            ->modalHeading('Retirar de diseños inactivos y eliminar')
            ->modalDescription(fn(MediaAsset $record)=>app(MediaDeletionService::class)->classify($record)['message'])
            ->action(function(MediaAsset $record){
                $result=app(MediaDeletionService::class)->delete($record,(int)auth()->id(),true);
                Notification::make()->title($result['deleted']?'Contenido eliminado':'No se pudo eliminar')->body($result['message'] ?? $result['error'] ?? '')->{$result['deleted']?'success':'danger'}()->send();
            }),
      ])
      ->bulkActions([
        Tables\Actions\BulkAction::make('delete_selected')
            ->label('Eliminar seleccionados')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->action(function($records){
                $result=app(MediaDeletionService::class)->bulk($records,(int)auth()->id(),true);
                Notification::make()->title($result['deleted'].' contenido(s) eliminado(s)')->body($result['blocked'].' bloqueado(s). Espacio liberado: '.number_format($result['bytes']/1048576,1).' MB.')->success()->send();
            }),
      ])
      ->defaultSort('created_at','desc'); }
    public static function getPages(): array { return ['index'=>Pages\ListMediaAssets::route('/'),'create'=>Pages\CreateMediaAsset::route('/create'),'edit'=>Pages\EditMediaAsset::route('/{record}/edit')]; }
    private static function maxUploadKilobytes(): int { $functional=(int)config('simpleview.max_upload_mb');$hard=max(1,(int)config('simpleview.max_upload_hard_mb'));return ($functional<=0? $hard:min($functional,$hard))*1024; }
    private static function uploadLimitText(): string { $functional=(int)config('simpleview.max_upload_mb');$hard=(int)config('simpleview.max_upload_hard_mb');return $functional<=0?"Sin límite funcional. Límite técnico: {$hard} MB por archivo.":'Máximo '.min($functional,$hard).' MB por archivo.'; }
}
