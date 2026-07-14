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
        $layout->load('zones.items.media');
        return ['layout_id'=>$layout->id,'name'=>$layout->name,'template'=>$layout->template_key,'version'=>$layout->version,
            'zones'=>$layout->zones->map(fn($z)=>['key'=>$z->zone_key,'position'=>$z->position,
                'transition'=>$z->transition_type,'transition_ms'=>$z->transition_duration_ms,
                'items'=>$z->items->map(fn($i)=>['id'=>$i->media->id,'type'=>$i->media->media_type,
                    'url'=>route('media.stream',$i->media),'duration_ms'=>$i->image_duration_ms ?: $z->image_duration_default_ms,
                    'fit'=>$i->image_fit ?: $z->image_fit_default,'transition'=>$i->transition_type ?: $z->transition_type,
                    'transition_ms'=>$i->transition_duration_ms ?: $z->transition_duration_ms])->values()])->values()];
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
        if ($draft->zones->flatMap->items->contains(fn($i)=>!Storage::disk('media')->exists($i->media->storage_path)))
            throw ValidationException::withMessages(['layout'=>'Falta uno de los archivos asignados.']);
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
}
