<?php
namespace App\Filament\Resources;
use App\Filament\Resources\LayoutResource\Pages; use App\Models\Layout; use App\Models\MediaAsset; use App\Services\PublicationService; use Filament\Forms; use Filament\Forms\Form; use Filament\Notifications\Notification; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class LayoutResource extends Resource {
 protected static ?string $model=Layout::class; protected static ?string $navigationIcon='heroicon-o-rectangle-group'; protected static ?string $navigationLabel='Editar pantalla'; protected static ?string $modelLabel='diseño'; protected static ?string $pluralModelLabel='diseños';
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
  Tables\Columns\TextColumn::make('name')->label('Nombre')->searchable(),Tables\Columns\TextColumn::make('template_key')->label('Plantilla')->formatStateUsing(fn($state)=>self::templates()[$state]??$state),Tables\Columns\TextColumn::make('state')->label('Estado')->badge(),Tables\Columns\TextColumn::make('published_at')->label('Publicado')->dateTime('d/m/Y H:i'),
  ])->actions([Tables\Actions\EditAction::make()->visible(fn(Layout $record)=>$record->state==='draft'),Tables\Actions\Action::make('preview')->label('Vista previa')->icon('heroicon-o-eye')->visible(fn(Layout $record)=>$record->state==='draft')->url(fn(Layout $record)=>route('preview',['layout'=>$record]))->openUrlInNewTab(),Tables\Actions\Action::make('publish')->label('Publicar')->color('success')->icon('heroicon-o-megaphone')->visible(fn(Layout $record)=>$record->state==='draft')->action(function(Layout $record){app(PublicationService::class)->publish($record,auth()->id());Notification::make()->success()->title('Pantalla publicada')->send();})]);}
 public static function templates():array{return['full'=>'Pantalla completa','columns'=>'Dos columnas','rows'=>'Dos filas','grid'=>'Cuadrícula 2 × 2','right_sidebar'=>'Dos medios y lateral derecho','left_sidebar'=>'Lateral izquierdo y dos medios'];}
 public static function zoneCount(string $key):int{return match($key){'full'=>1,'grid'=>4,'right_sidebar','left_sidebar'=>3,default=>2};}
 public static function getPages():array{return['index'=>Pages\ListLayouts::route('/'),'create'=>Pages\CreateLayout::route('/create'),'edit'=>Pages\EditLayout::route('/{record}/edit')];}
}
