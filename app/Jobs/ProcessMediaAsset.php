<?php

namespace App\Jobs;

use App\Models\MediaAsset;
use App\Services\MediaIngestionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessMediaAsset implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;
    public int $timeout = 900;
    public array $backoff = [10, 30];

    public function __construct(public int $mediaAssetId)
    {
        $this->onQueue('media');
    }

    public function handle(MediaIngestionService $ingestion): void
    {
        $ingestion->process($this->mediaAssetId);
    }

    public function failed(?\Throwable $exception): void
    {
        MediaAsset::find($this->mediaAssetId)?->update([
            'status' => 'error',
            'validation_message' => 'El procesamiento se interrumpió. Vuelve a subir el archivo.',
            'processing_completed_at' => now(),
        ]);
    }
}
