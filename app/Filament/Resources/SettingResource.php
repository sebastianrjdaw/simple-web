<?php
namespace App\Filament\Resources;
use App\Filament\Resources\SettingResource\Pages; use App\Models\MediaAsset; use App\Models\Setting;
use Filament\Forms; use Filament\Forms\Form; use Filament\Resources\Resource; use Filament\Tables; use Filament\Tables\Table;
class SettingResource extends Resource {
 protected static ?string $model=Setting::class; protected static ?string $navigationIcon='heroicon-o-cog-6-tooth'; protected static ?string $navigationLabel='Configuración'; protected static ?string $modelLabel='ajuste';
 private static function selectable(?Setting $record):bool{return in_array($record?->key,['playback_override','after_hours_mode','default_image_fit','default_transition_type','fallback_media_asset_id','backup_frequency_days','backup_type','backup_destination','backup_automatic_enabled'],true);}
 public static function form(Form $form):Form{return $form->schema([
  Forms\Components\TextInput::make('key')->label('Ajuste')->disabled(),
  Forms\Components\Select::make('value')->label('Valor')->options(fn(?Setting $record)=>match($record?->key){
   'playback_override'=>['normal'=>'Seguir horario','force'=>'Reproducir ahora','pause'=>'Pausar reproducción'],
   'after_hours_mode'=>['fallback'=>'Imagen corporativa','black'=>'Fondo negro'], 'default_image_fit'=>['cover'=>'Cubrir','contain'=>'Contener'],
   'default_transition_type'=>['fade'=>'Fundido','cut'=>'Corte'], 'fallback_media_asset_id'=>MediaAsset::where('media_type','image')->where('status','ready')->pluck('display_name','id')->all(),
   'backup_frequency_days'=>['1'=>'Todos los días','2'=>'Cada dos días'], 'backup_type'=>['configuration'=>'Configuración y base de datos','full'=>'Completa con multimedia'], 'backup_destination'=>['local'=>'Almacenamiento local'], 'backup_automatic_enabled'=>['1'=>'Activadas'], default=>[],
  })->visible(fn(?Setting $record)=>self::selectable($record)),
  Forms\Components\TextInput::make('value')->label('Valor')->visible(fn(?Setting $record)=>!self::selectable($record)),
 ]);}
 public static function table(Table $table):Table{return $table->columns([Tables\Columns\TextColumn::make('key')->label('Ajuste')->formatStateUsing(fn($state)=>str_replace('_',' ',ucfirst($state)))->searchable()->sortable(),Tables\Columns\TextColumn::make('value')->label('Valor')->searchable()])->filters([Tables\Filters\Filter::make('backups')->label('Copias de seguridad')->query(fn($q)=>$q->where('key','like','backup_%')),Tables\Filters\Filter::make('playback')->label('Reproducción')->query(fn($q)=>$q->whereIn('key',['playback_override','after_hours_mode','fallback_media_asset_id'])),])->actions([Tables\Actions\EditAction::make()])->paginated(25);}
 public static function getPages():array{return['index'=>Pages\ListSettings::route('/'),'edit'=>Pages\EditSetting::route('/{record}/edit')];}
}
