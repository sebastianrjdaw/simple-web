<?php

namespace App\Services;

use App\Models\Layout;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PublicationService
{
    public function __construct(private StorageMetricsService $storageMetrics, private StoragePolicyService $storagePolicy) {}
    public function config(Layout $layout): array
    {
        $layout->load('zones.items.media.fallbackMedia');
        return ['layout_id'=>$layout->id,'name'=>$layout->name,'template'=>$layout->template_key,'version'=>$layout->version,
            'zones'=>$layout->zones->map(fn($z)=>['key'=>$z->zone_key,'position'=>$z->position,
                'transition'=>$z->transition_type,'transition_ms'=>$z->transition_duration_ms,
                'items'=>$z->items->map(fn($i)=>$this->itemConfig($i,$z))->values()])->values()];
    }

    public function publish(Layout $draft, int $userId): Layout
    {
        $storage=$this->storageMetrics->current();
        if(!$this->storagePolicy->operationAllowed($storage)) throw ValidationException::withMessages(['layout'=>'El almacenamiento está en estado crítico. Libera espacio antes de publicar.']);
        $draft->load('zones.items.media');
        if ($draft->zones->isEmpty() || $draft->zones->contains(fn($z)=>$z->items->isEmpty()))
            throw ValidationException::withMessages(['layout'=>'Todas las zonas deben contener al menos un archivo.']);
        if ($draft->zones->flatMap->items->contains(fn($i)=>$i->media->status !== 'ready'))
            throw ValidationException::withMessages(['layout'=>'Hay archivos que no están listos.']);
        if ($draft->zones->flatMap->items->contains(fn($i)=>$i->media->media_type !== 'web_embed' && !Storage::disk('media')->exists($i->media->storage_path)))
            throw ValidationException::withMessages(['layout'=>'Falta uno de los archivos asignados.']);
        if ($draft->zones->filter(fn($z)=>$z->items->contains(fn($i)=>$i->media->media_type==='web_embed') && $z->items->count()>1)->isNotEmpty())
            throw ValidationException::withMessages(['layout'=>'AIMHARDER debe ocupar una zona completa sin mezclarse con imágenes o vídeos.']);
        if ($draft->zones->flatMap->items->where('media.media_type','web_embed')->count() > config('simpleview.web_embed.max_iframes_per_screen'))
            throw ValidationException::withMessages(['layout'=>'Hay demasiadas páginas web en el diseño. Usa como máximo '.config('simpleview.web_embed.max_iframes_per_screen').'.']);
        return DB::transaction(function() use($draft,$userId) {
            Layout::where('state','published')->update(['state'=>'archived']);
            $version=((int)Layout::max('version'))+1;
            $published=$draft->replicate(); $published->fill(['state'=>'published','version'=>$version,'published_at'=>now(),'published_by'=>$userId]);
            $published->snapshot_json=[]; $published->save();
            foreach($draft->zones as $zone){ $copy=$zone->replicate(); $copy->layout_id=$published->id; $copy->save(); foreach($zone->items as $item){$ic=$item->replicate();$ic->layout_zone_id=$copy->id;$ic->save();}}
            $published->snapshot_json=$this->config($published); $published->save();
            MediaAsset::whereIn('id',$published->zones->flatMap->items->pluck('media_asset_id'))->update(['last_used_at'=>now()]);
            DB::table('settings')->updateOrInsert(['key'=>'active_publication_version'],['value'=>(string)$version,'type'=>'integer','updated_at'=>now(),'created_at'=>now()]);
            return $published;
        });
    }

    private function itemConfig($item, $zone): array
    {
        $media = $item->media;
        if ($media->media_type === 'web_embed') {
            $options = $media->embed_options_json ?: [];
            return [
                'id' => $media->id,
                'type' => 'web_embed',
                'provider' => $media->provider ?: 'aimharder',
                'name' => $media->display_name,
                'url' => $media->embed_url,
                'refresh_interval_minutes' => (int) ($options['refresh_interval_minutes'] ?? 15),
                'interaction_enabled' => (bool) ($options['interaction_enabled'] ?? false),
                'scroll_mode' => $options['scroll_mode'] ?? 'full',
                'fallback_url' => $media->fallbackMedia ? route('media.stream', $media->fallbackMedia) : null,
                'validation_status' => $media->validation_status ?: 'pending',
            ];
        }

        return [
            'id'=>$media->id,'type'=>$media->media_type,
            'url'=>route('media.stream',$media),'duration_ms'=>$item->image_duration_ms ?: $zone->image_duration_default_ms,
            'fit'=>$item->image_fit ?: $zone->image_fit_default,'transition'=>$item->transition_type ?: $zone->transition_type,
            'transition_ms'=>$item->transition_duration_ms ?: $zone->transition_duration_ms,
        ];
    }
}
