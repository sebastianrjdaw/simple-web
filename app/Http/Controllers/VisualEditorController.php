<?php

namespace App\Http\Controllers;

use App\Filament\Resources\LayoutResource;
use App\Models\Layout;
use App\Models\MediaAsset;
use App\Services\AimHarderEmbedService;
use App\Services\MediaDeletionService;
use App\Services\MediaIngestionService;
use App\Services\PublicationService;
use App\Services\UploadCapacityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class VisualEditorController extends Controller
{
    private function enabled(): void
    {
        abort_unless(config('simpleview.visual_editor_enabled'), 404);
    }

    private function draft(Layout $layout): void
    {
        abort_unless($layout->state === 'draft', 403, 'Solo se pueden editar borradores.');
    }

    public function show(Layout $layout)
    {
        $this->enabled();
        $this->draft($layout);

        return view('visual-editor', ['layout' => $layout, 'templates' => LayoutResource::templates()]);
    }

    public function state(Layout $layout)
    {
        $this->draft($layout);

        return response()->json($this->payload($layout));
    }

    public function preflight(Request $request, UploadCapacityService $capacity)
    {
        $data = $request->validate(['sizes' => 'required|array|max:100', 'sizes.*' => 'integer|min:1']);
        $result = $capacity->check(array_sum($data['sizes']));

        return response()->json(['allowed' => $result['allowed'], 'required_bytes' => $result['required_bytes'], 'shortfall_bytes' => $result['shortfall_bytes'], 'message' => $result['allowed'] ? 'Hay espacio disponible.' : 'No hay espacio suficiente para completar la subida manteniendo la reserva protegida.'], $result['allowed'] ? 200 : 422);
    }

    public function save(Request $request, Layout $layout)
    {
        $this->draft($layout);
        $data = $request->validate(['name' => 'required|string|max:255', 'template_key' => ['required', Rule::in(array_keys(LayoutResource::templates()))], 'zones' => 'required|array|min:1|max:4', 'zones.*.key' => 'required|string|max:32', 'zones.*.image_fit_default' => ['required', Rule::in(['cover', 'contain'])], 'zones.*.image_duration_default_ms' => 'required|integer|min:250|max:86400000', 'zones.*.transition_type' => ['required', Rule::in(['cut', 'fade'])], 'zones.*.transition_duration_ms' => 'required|integer|min:0|max:10000', 'zones.*.items' => 'array', 'zones.*.items.*.media_asset_id' => 'required|integer|exists:media_assets,id', 'zones.*.items.*.image_duration_ms' => 'nullable|integer|min:250|max:86400000', 'zones.*.items.*.image_fit' => ['nullable', Rule::in(['cover', 'contain'])], 'zones.*.items.*.transition_type' => ['nullable', Rule::in(['cut', 'fade'])], 'zones.*.items.*.transition_duration_ms' => 'nullable|integer|min:0|max:10000']);
        if (count($data['zones']) !== LayoutResource::zoneCount($data['template_key'])) {
            return response()->json(['message' => 'La cantidad de zonas no corresponde a la plantilla.'], 422);
        }
        DB::transaction(function () use ($layout, $data) {
            $layout->update(['name' => $data['name'], 'template_key' => $data['template_key']]);
            $keep = [];
            foreach (array_values($data['zones']) as $position => $zoneData) {
                $zone = $layout->zones()->updateOrCreate(['zone_key' => $zoneData['key']], ['position' => $position + 1, 'image_fit_default' => $zoneData['image_fit_default'], 'image_duration_default_ms' => $zoneData['image_duration_default_ms'], 'transition_type' => $zoneData['transition_type'], 'transition_duration_ms' => $zoneData['transition_duration_ms']]);
                $keep[] = $zone->id;
                $zone->items()->delete();
                foreach (array_values($zoneData['items'] ?? []) as $order => $item) {
                    $zone->items()->create(['media_asset_id' => $item['media_asset_id'], 'sort_order' => $order, 'image_duration_ms' => $item['image_duration_ms'] ?? null, 'image_fit' => $item['image_fit'] ?? null, 'transition_type' => $item['transition_type'] ?? null, 'transition_duration_ms' => $item['transition_duration_ms'] ?? null]);
                }
            }$layout->zones()->whereNotIn('id', $keep)->delete();
        });

        return response()->json(['saved_at' => now()->toIso8601String(), 'state' => $this->payload($layout)]);
    }

    public function upload(Request $request, Layout $layout, MediaIngestionService $ingestion)
    {
        $this->draft($layout);
        $request->validate(['zone_key' => 'required|string|max:32', 'files' => 'required|array|max:20', 'files.*' => ['file', 'mimetypes:image/jpeg,image/png,image/webp,video/mp4']]);
        $items = [];
        foreach ($request->file('files') as $file) {
            try {
                $media = $ingestion->enqueue($file);
                $items[] = $this->processingPayload($media);
            } catch (\Throwable $e) {
                $items[] = ['name' => $file->getClientOriginalName(), 'error' => $e instanceof ValidationException ? (string) collect($e->errors())->flatten()->first() : 'No se pudo guardar el archivo.'];
            }
        }

        return response()->json(['items' => $items], 202);
    }

    public function addAimHarder(Request $request, Layout $layout, AimHarderEmbedService $embeds)
    {
        $this->draft($layout);
        $data = $request->validate([
            'content' => 'required|string|max:4096',
            'name' => 'nullable|string|max:255',
        ]);
        $media = $embeds->create($data['content'], $data['name'] ?? null);

        return response()->json([
            'message' => 'Contenido de AimHarder añadido a la biblioteca.',
            'media' => $this->mediaPayload($media),
        ], 201);
    }

    public function processingStatus(Request $request, Layout $layout)
    {
        $this->draft($layout);
        $data = $request->validate(['ids' => 'required|array|max:100', 'ids.*' => 'integer']);
        $items = MediaAsset::with('processingResult')->whereIn('id', $data['ids'])->get()->map(function (MediaAsset $media) {
            if ($media->status === 'duplicate' && $media->processingResult) {
                return ['request_id' => $media->id, 'status' => 'ready', 'duplicate' => true, 'message' => $media->validation_message, 'media' => $this->mediaPayload($media->processingResult)];
            }if ($media->status === 'ready') {
                return ['request_id' => $media->id, 'status' => 'ready', 'media' => $this->mediaPayload($media)];
            }

            return ['request_id' => $media->id, 'status' => $media->status, 'name' => $media->display_name, 'message' => $media->validation_message];
        });

        return response()->json(['items' => $items]);
    }

    public function mediaUses(Layout $layout, MediaAsset $media, MediaDeletionService $deletion)
    {
        $this->draft($layout);

        return response()->json($deletion->classify($media));
    }

    public function deleteMedia(Request $request, Layout $layout, MediaAsset $media, MediaDeletionService $deletion)
    {
        $this->draft($layout);
        $data = $request->validate(['detach_inactive' => 'sometimes|boolean']);
        $result = $deletion->delete($media, (int) auth()->id(), (bool) ($data['detach_inactive'] ?? false));

        return response()->json($result, $result['deleted'] ? 200 : 422);
    }

    public function publish(Layout $layout, PublicationService $publication)
    {
        $this->draft($layout);
        try {
            $published = $publication->publish($layout, (int) auth()->id());

            return response()->json(['message' => 'Pantalla publicada. El reproductor se actualizará en un máximo de tres segundos.', 'version' => $published->version]);
        } catch (ValidationException $e) {
            return response()->json(['message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Throwable) {
            return response()->json(['message' => 'No se pudo publicar. Revisa el almacenamiento y el contenido de todas las zonas.'], 500);
        }
    }

    private function payload(Layout $layout): array
    {
        $layout->load('zones.items.media');

        return ['layout' => ['id' => $layout->id, 'name' => $layout->name, 'template_key' => $layout->template_key, 'state' => $layout->state, 'zones' => $layout->zones->map(fn ($z) => ['key' => $z->zone_key, 'image_fit_default' => $z->image_fit_default, 'image_duration_default_ms' => $z->image_duration_default_ms, 'transition_type' => $z->transition_type, 'transition_duration_ms' => $z->transition_duration_ms, 'items' => $z->items->map(fn ($i) => array_merge($this->mediaPayload($i->media), ['media_asset_id' => $i->media_asset_id, 'image_duration_ms' => $i->image_duration_ms, 'image_fit' => $i->image_fit, 'transition_type' => $i->transition_type, 'transition_duration_ms' => $i->transition_duration_ms]))->values()])->values()], 'media' => MediaAsset::where('status', 'ready')->latest()->limit(500)->get()->map(fn ($m) => $this->mediaPayload($m)), 'processing' => MediaAsset::where('status', 'processing')->latest()->limit(100)->get()->map(fn ($m) => $this->processingPayload($m))];
    }

    private function mediaPayload(MediaAsset $m): array
    {
        return ['id' => $m->id, 'media_asset_id' => $m->id, 'name' => $m->display_name, 'type' => $m->media_type, 'mime' => $m->mime_type, 'size' => $m->file_size, 'width' => $m->width, 'height' => $m->height, 'duration_ms' => $m->duration_ms, 'default_duration_ms' => $m->media_type === 'embed' ? config('simpleview.embed_default_duration_ms') : null, 'provider' => $m->external_provider, 'external_url' => $m->external_url, 'thumbnail' => $m->media_type === 'embed' ? asset('images/aimharder-embed.svg') : route('media.thumbnail', $m)];
    }

    private function processingPayload(MediaAsset $m): array
    {
        return ['id' => $m->id, 'request_id' => $m->id, 'name' => $m->display_name, 'type' => $m->media_type, 'size' => $m->file_size, 'status' => $m->status];
    }
}
