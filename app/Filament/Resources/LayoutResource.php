<?php
namespace App\Filament\Resources;
use App\Filament\Resources\LayoutResource\Pages; use App\Models\Layout; use App\Models\MediaAsset; use App\Services\LayoutDeletionService; use App\Services\PublicationService; use Filament\Forms; use Filament\Forms\Form; use Filament\Notifications\Notification; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table; use Illuminate\Database\Eloquent\Builder;
class LayoutResource extends Resource {
 protected static ?string $model=Layout::class; protected static ?string $navigationIcon='heroicon-o-rectangle-group'; protected static ?string $navigationLabel='Diseños'; protected static ?string $modelLabel='diseño'; protected static ?string $pluralModelLabel='diseños';
 public static function form(Form $form):Form{return $form->schema([
  Forms\Components\TextInput::make('name')->label('Nombre')->required(),
  Forms\Components\Select::make('template_key')->label('Plantilla')->options(self::templates())->required()->disabledOn('edit'),
  Forms\Components\Repeater::make('zones')->label('Zonas')->relationship()->orderColumn('position')->schema([
   Forms\Components\Hidden::make('zone_key')->required(),
   Forms\Components\Hidden::make('position'),
   Forms\Components\Select::make('image_fit_default')->label('Ajuste de imágenes')->options(['cover'=>'Cubrir','contain'=>'Contener'])->default('cover')->required(),
   Forms\Components\TextInput::make('image_duration_default_ms')->label('Duración imagen (ms)')->numeric()->default(10000)->required(),
   Forms\Components\Select::make('transition_type')->label('Transición')->options(['cut'=>'Corte','fade'=>'Fundido'])->default('fade')->required(),
   Forms\Components\Select::make('transition_duration_ms')->label('Duración fundido')->options([250=>'250 ms',500=>'500 ms',1000=>'1000 ms'])->default(500)->required(),
   Forms\Components\Repeater::make('items')->label('Lista de reproducción')->relationship()->orderColumn('sort_order')->reorderable()->schema([
    Forms\Components\Select::make('media_asset_id')->label('Contenido')->options(fn()=>MediaAsset::where('status','ready')->pluck('display_name','id'))->searchable()->required(),
    Forms\Components\TextInput::make('image_duration_ms')->label('Duración personalizada (ms)')->numeric()->nullable(),
    Forms\Components\Select::make('image_fit')->label('Ajuste')->options(['cover'=>'Cubrir','contain'=>'Contener'])->placeholder('Usar valor de zona'),
   ])->columns(3)->collapsible()->itemLabel(fn(array $state)=>MediaAsset::find($state['media_asset_id']??null)?->display_name),
  ])->columns(2)->collapsible()->addable(false)->deletable(false)->reorderable(false)->itemLabel(fn(array $state)=>'Zona '.($state['position']??''))->columnSpanFull()->visibleOn('edit'),
 ]);}
 public static function table(Table $table):Table{return $table->columns([
  Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable()->sortable(),Tables\Columns\TextColumn::make('template_key')->label('Plantilla')->formatStateUsing(fn($state)=>self::templates()[$state]??$state)->sortable(),Tables\Columns\TextColumn::make('state')->label('Estado')->badge()->formatStateUsing(fn($state)=>['draft'=>'Borrador','published'=>'Publicado','archived'=>'Archivado'][$state]??$state),Tables\Columns\TextColumn::make('zones_count')->counts('zones')->label('Zonas')->sortable(),Tables\Columns\TextColumn::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->sortable(),Tables\Columns\TextColumn::make('published_at')->label('Publicado')->dateTime('d/m/Y H:i')->sortable(),
  ])->filters([
   Tables\Filters\SelectFilter::make('template_key')->label('Plantilla')->options(self::templates()),
   Tables\Filters\SelectFilter::make('state')->label('Estado')->options(['draft'=>'Borrador','published'=>'Publicado','archived'=>'Archivado']),
   Tables\Filters\Filter::make('inactive')->label('No activos')->query(fn(Builder $query)=>$query->where(fn($q)=>$q->where('state','<>','published')->orWhere('version','<>',(int)\App\Models\Setting::valueOf('active_publication_version',0)))),
  ])->actions([Tables\Actions\Action::make('visual')->label('Editar')->icon('heroicon-o-squares-2x2')->visible(fn(Layout $record)=>$record->state==='draft'&&config('simpleview.visual_editor_enabled'))->url(fn(Layout $record)=>route('visual-editor',$record)),Tables\Actions\Action::make('preview')->label('Vista previa')->icon('heroicon-o-eye')->visible(fn(Layout $record)=>$record->state==='draft')->url(fn(Layout $record)=>route('preview',['layout'=>$record]))->openUrlInNewTab(),Tables\Actions\Action::make('publish')->label('Publicar')->color('success')->icon('heroicon-o-megaphone')->visible(fn(Layout $record)=>$record->state==='draft')->action(function(Layout $record){app(PublicationService::class)->publish($record,auth()->id());Notification::make()->success()->title('Pantalla publicada')->send();}),Tables\Actions\Action::make('delete_safe')->label('Eliminar')->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()->modalHeading(fn(Layout $record)=>'Eliminar '.$record->name)->modalDescription(fn(Layout $record)=>app(LayoutDeletionService::class)->classify($record)['message'])->action(function(Layout $record){$result=app(LayoutDeletionService::class)->delete($record,(int)auth()->id());Notification::make()->title($result['deleted']?'Diseño eliminado':'No se pudo eliminar')->body($result['message'] ?? $result['error'] ?? '')->{$result['deleted']?'success':'warning'}()->send();})])
  ->bulkActions([Tables\Actions\BulkAction::make('delete_selected')->label('Eliminar seleccionados')->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()->action(function($records){$result=app(LayoutDeletionService::class)->bulk($records,(int)auth()->id());Notification::make()->title($result['deleted'].' diseño(s) eliminado(s)')->body($result['blocked'].' bloqueado(s). Los archivos multimedia se conservan.')->success()->send();})])
  ->defaultSort('updated_at','desc');}
 public static function templates():array{return['full'=>'Pantalla completa','columns'=>'Dos columnas','rows'=>'Dos filas','grid'=>'Cuadrícula 2 × 2','right_sidebar'=>'Dos medios y lateral derecho','left_sidebar'=>'Lateral izquierdo y dos medios'];}
 public static function zoneCount(string $key):int{return match($key){'full'=>1,'grid'=>4,'right_sidebar','left_sidebar'=>3,default=>2};}
 public static function getPages():array{return['index'=>Pages\ListLayouts::route('/'),'create'=>Pages\CreateLayout::route('/create'),'edit'=>Pages\EditLayout::route('/{record}/classic')];}
}
