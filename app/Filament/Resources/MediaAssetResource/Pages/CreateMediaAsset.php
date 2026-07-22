<?php

namespace App\Filament\Resources\MediaAssetResource\Pages;

use App\Filament\Resources\MediaAssetResource;
use App\Services\MediaIngestionService;
use App\Services\UploadCapacityService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CreateMediaAsset extends CreateRecord
{
    protected static string $resource = MediaAssetResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $paths = (array) $data['storage_path'];
        $names = (array) $data['original_filename'];
        $first = null;
        try {
            app(UploadCapacityService::class)->ensure(array_sum(array_map(fn ($p) => Storage::disk('media')->size($p), $paths)), true);
        } catch (\Throwable $e) {
            Storage::disk('media')->delete($paths);
            throw $e;
        }
        foreach ($paths as $key => $path) {
            $original = $names[$key] ?? $names[$path] ?? basename($path);
            $record = app(MediaIngestionService::class)->enqueueStored($path, $original);
            $first ??= $record;
        }

        return $first;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()->success()->title('Archivos recibidos')->body('Puedes seguir trabajando. Las miniaturas y la validación se completarán en segundo plano.');
    }
}
