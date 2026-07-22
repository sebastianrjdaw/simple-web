<?php

namespace App\Services;

use App\Jobs\ProcessMediaAsset;
use App\Models\MediaAsset;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MediaIngestionService
{
    public function __construct(private UploadCapacityService $capacity, private MediaInspector $inspector) {}

    public function enqueue(UploadedFile $file): MediaAsset
    {
        $this->capacity->ensure((int) $file->getSize());
        $path = $file->store('originals', 'media');

        return $this->enqueueStored($path, $file->getClientOriginalName());
    }

    public function enqueueStored(string $path, string $originalName): MediaAsset
    {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $token = (string) Str::uuid();
        $asset = MediaAsset::create([
            'display_name' => pathinfo($originalName, PATHINFO_FILENAME),
            'original_filename' => $originalName,
            'storage_path' => $path,
            'mime_type' => $extension === 'mp4' ? 'video/mp4' : 'image/'.$extension,
            'media_type' => $extension === 'mp4' ? 'video' : 'image',
            'extension' => $extension,
            'file_size' => Storage::disk('media')->size($path),
            'sha256' => hash('sha256', 'processing:'.$token),
            'status' => 'processing',
        ]);

        ProcessMediaAsset::dispatch($asset->id);

        return $asset;
    }

    public function process(int $mediaAssetId): void
    {
        $asset = MediaAsset::find($mediaAssetId);
        if (! $asset || $asset->status !== 'processing') {
            return;
        }

        $asset->update(['processing_started_at' => now(), 'validation_message' => null]);

        try {
            $info = $this->inspector->inspect($asset->storage_path, $asset->original_filename);
            $existing = MediaAsset::withTrashed()->where('sha256', $info['sha256'])->where('id', '<>', $asset->id)->first();

            if ($existing?->trashed()) {
                $existing->forceDelete();
                $existing = null;
            }

            if ($existing?->status === 'ready') {
                Storage::disk('media')->delete($asset->storage_path);
                $asset->update([
                    'status' => 'duplicate',
                    'processing_result_media_id' => $existing->id,
                    'processing_completed_at' => now(),
                    'validation_message' => 'El archivo ya existía; se ha reutilizado el contenido original.',
                ]);

                return;
            }

            $thumbnail = $this->inspector->thumbnail($asset->storage_path, $info['media_type'], $info['sha256']);
            $asset->update($info + [
                'thumbnail_path' => $thumbnail,
                'storage_verified_at' => now(),
                'processing_completed_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            Storage::disk('media')->delete($asset->storage_path);
            $asset->update([
                'status' => 'error',
                'validation_message' => $this->publicError($exception),
                'processing_completed_at' => now(),
            ]);
        }
    }

    private function publicError(\Throwable $exception): string
    {
        if ($exception instanceof ValidationException) {
            return (string) collect($exception->errors())->flatten()->first();
        }

        report($exception);

        return 'No se pudo procesar el archivo. Comprueba que no esté dañado y vuelve a intentarlo.';
    }
}
