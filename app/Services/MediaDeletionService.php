<?php

namespace App\Services;

use App\Models\AdminActivityEvent;
use App\Models\Layout;
use App\Models\MediaAsset;
use App\Models\PlaylistItem;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MediaDeletionService
{
    public function uses(MediaAsset $asset): array
    {
        $assetId = $asset->getKey();
        $activeVersion = (int) Setting::valueOf('active_publication_version', 0);
        $fallbackId = (int) Setting::valueOf('fallback_media_asset_id', 0);

        $items = PlaylistItem::query()
            ->where('media_asset_id', $assetId)
            ->with('zone.layout')
            ->get();

        $active = [];
        $inactive = [];

        foreach ($items as $item) {
            $layout = $item->zone?->layout;
            if (!$layout) {
                continue;
            }

            $entry = [
                'layout_id' => $layout->id,
                'layout_name' => $layout->name,
                'layout_state' => $layout->state,
                'layout_version' => $layout->version,
                'zone_key' => $item->zone->zone_key,
                'zone_position' => $item->zone->position,
            ];

            if ($layout->state === 'published' && (int) $layout->version === $activeVersion) {
                $active[] = $entry + ['reason' => 'Publicación activa'];
            } else {
                $inactive[] = $entry;
            }
        }

        $configuration = [];
        if ($fallbackId === $assetId) {
            $configuration[] = [
                'setting' => 'fallback_media_asset_id',
                'label' => 'Imagen de respaldo',
                'action' => route('filament.admin.resources.settings.index'),
            ];
        }
        foreach (MediaAsset::where('fallback_media_asset_id', $assetId)->get(['id', 'display_name']) as $embed) {
            $configuration[] = [
                'setting' => 'web_embed_fallback',
                'label' => 'Imagen de respaldo de "'.$embed->display_name.'"',
                'action' => route('filament.admin.resources.media-assets.index'),
            ];
        }

        return [
            'active' => $this->uniqueUses($active),
            'inactive' => $this->uniqueUses($inactive),
            'configuration' => $configuration,
            'total_references' => $items->count() + count($configuration),
        ];
    }

    public function classify(MediaAsset $asset): array
    {
        $uses = $this->uses($asset);
        $blocked = [];
        if ($uses['active']) {
            $blocked[] = 'active_publication';
        }
        if ($uses['configuration']) {
            $blocked[] = 'configuration';
        }

        $status = match (true) {
            $blocked !== [] => 'blocked',
            $uses['inactive'] !== [] => 'inactive_only',
            default => 'unused',
        };

        return [
            'status' => $status,
            'blocked_reasons' => $blocked,
            'can_delete' => $status === 'unused',
            'can_detach_and_delete' => $status === 'inactive_only',
            'uses' => $uses,
            'bytes' => (int) $asset->file_size,
            'message' => $this->message($asset, $status, $uses),
        ];
    }

    public function delete(MediaAsset $asset, int $userId, bool $detachInactive = false): array
    {
        $asset = MediaAsset::withTrashed()->findOrFail($asset->id);
        $classification = $this->classify($asset);

        if ($classification['status'] === 'blocked') {
            $this->record($userId, $asset, 'blocked', $classification);
            return ['deleted' => false, 'status' => 'blocked'] + $classification;
        }

        if ($classification['status'] === 'inactive_only' && !$detachInactive) {
            $this->record($userId, $asset, 'needs_detach', $classification);
            return ['deleted' => false, 'status' => 'needs_detach'] + $classification;
        }

        $mediaPath = $asset->storage_path;
        $thumbnailPath = $asset->thumbnail_path;
        $bytes = (int) $asset->file_size;

        try {
            DB::transaction(function () use ($asset, $classification, $detachInactive, $mediaPath, $thumbnailPath) {
                $fresh = MediaAsset::lockForUpdate()->findOrFail($asset->id);
                $current = $this->classify($fresh);

                if ($current['status'] === 'blocked') {
                    throw new \RuntimeException('El contenido ha pasado a estar en uso por la publicación activa o la configuración.');
                }

                if ($current['status'] === 'inactive_only' && !$detachInactive) {
                    throw new \RuntimeException('El contenido sigue usado en diseños no activos.');
                }

                if ($detachInactive) {
                    PlaylistItem::where('media_asset_id', $fresh->id)->delete();
                }

                $this->deletePhysicalFiles($mediaPath, $thumbnailPath);
                $fresh->delete();
            });

            app(StorageMetricsService::class)->measure();
            $result = ['deleted' => true, 'status' => 'deleted', 'bytes' => $bytes, 'message' => 'Se ha eliminado "'.$asset->display_name.'".'];
            $this->record($userId, $asset, 'deleted', $classification, null, $bytes);
            return $result + $classification;
        } catch (\Throwable $e) {
            $this->record($userId, $asset, 'error', $classification, $e->getMessage());
            return ['deleted' => false, 'status' => 'error', 'error' => $e->getMessage()] + $classification;
        }
    }

    public function bulk(iterable $assets, int $userId, bool $detachInactive = false): array
    {
        $results = [];
        foreach ($assets as $asset) {
            $results[] = ['id' => $asset->id, 'name' => $asset->display_name] + $this->delete($asset, $userId, $detachInactive);
        }

        return [
            'results' => $results,
            'deleted' => collect($results)->where('deleted', true)->count(),
            'blocked' => collect($results)->whereIn('status', ['blocked', 'needs_detach', 'error'])->count(),
            'bytes' => collect($results)->where('deleted', true)->sum('bytes'),
        ];
    }

    private function deletePhysicalFiles(?string $mediaPath, ?string $thumbnailPath): void
    {
        if ($mediaPath && Storage::disk('media')->exists($mediaPath) && !Storage::disk('media')->delete($mediaPath)) {
            throw new \RuntimeException('No se pudo eliminar el archivo original.');
        }

        if ($thumbnailPath && Storage::disk('thumbnails')->exists($thumbnailPath) && !Storage::disk('thumbnails')->delete($thumbnailPath)) {
            throw new \RuntimeException('No se pudo eliminar la miniatura.');
        }
    }

    private function message(MediaAsset $asset, string $status, array $uses): string
    {
        if ($status === 'blocked') {
            if ($uses['active']) {
                return 'No se puede eliminar este contenido porque se está mostrando actualmente. Retíralo del diseño, publica los cambios y vuelve a intentarlo.';
            }
            return 'No se puede eliminar este contenido porque se usa en Configuración. Sustitúyelo primero.';
        }

        if ($status === 'inactive_only') {
            $count = count($uses['inactive']);
            return "Este contenido se utiliza en {$count} diseño(s) no activo(s). Puede retirarse de esos diseños y eliminarse definitivamente.";
        }

        if ($asset->media_type === 'web_embed') {
            return 'Vas a eliminar definitivamente "'.$asset->display_name.'". Se eliminará su URL y configuración, no archivos multimedia.';
        }

        return 'Vas a eliminar definitivamente "'.$asset->display_name.'". Se liberarán '.$this->humanBytes((int) $asset->file_size).'.';
    }

    private function record(int $userId, MediaAsset $asset, string $result, array $details, ?string $error = null, int $bytes = 0): void
    {
        AdminActivityEvent::create([
            'user_id' => $userId ?: null,
            'action' => 'media.delete',
            'subject_type' => MediaAsset::class,
            'subject_id' => $asset->id,
            'result' => $result,
            'bytes' => $bytes,
            'details_json' => $details,
            'error_message' => $error,
        ]);
    }

    private function uniqueUses(array $uses): array
    {
        return collect($uses)
            ->unique(fn ($use) => $use['layout_id'].'-'.$use['zone_key'].'-'.($use['reason'] ?? ''))
            ->values()
            ->all();
    }

    private function humanBytes(int $bytes): string
    {
        return $bytes >= 1073741824
            ? number_format($bytes / 1073741824, 1, ',', '.').' GB'
            : number_format($bytes / 1048576, 1, ',', '.').' MB';
    }
}
