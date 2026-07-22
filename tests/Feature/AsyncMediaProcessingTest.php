<?php

namespace Tests\Feature;

use App\Jobs\ProcessMediaAsset;
use App\Models\Layout;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\MediaIngestionService;
use App\Services\MediaInspector;
use App\Services\UploadCapacityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AsyncMediaProcessingTest extends TestCase
{
    use RefreshDatabase;

    public function test_stored_upload_is_registered_and_dispatched_without_inspection(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('originals/photo.jpg', 'pending-content');
        Queue::fake();

        $asset = app(MediaIngestionService::class)->enqueueStored('originals/photo.jpg', 'Escaparate.jpg');

        $this->assertSame('processing', $asset->status);
        $this->assertSame('Escaparate', $asset->display_name);
        Queue::assertPushed(ProcessMediaAsset::class, fn (ProcessMediaAsset $job) => $job->mediaAssetId === $asset->id);
    }

    public function test_editor_status_resolves_a_duplicate_to_the_existing_asset(): void
    {
        $user = User::factory()->create();
        $layout = Layout::create(['name' => 'Escaparate', 'template_key' => 'full', 'state' => 'draft']);
        $layout->zones()->create(['zone_key' => 'zone_1', 'position' => 1]);
        $ready = $this->media(['status' => 'ready', 'sha256' => str_repeat('a', 64)]);
        $pending = $this->media([
            'status' => 'duplicate',
            'sha256' => str_repeat('b', 64),
            'processing_result_media_id' => $ready->id,
            'validation_message' => 'Contenido reutilizado.',
        ]);

        $this->actingAs($user)->postJson(route('visual-editor.processing-status', $layout), ['ids' => [$pending->id]])
            ->assertOk()
            ->assertJsonPath('items.0.status', 'ready')
            ->assertJsonPath('items.0.duplicate', true)
            ->assertJsonPath('items.0.media.id', $ready->id);
    }

    public function test_worker_marks_a_valid_asset_as_ready(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('originals/photo.jpg', 'image-content');
        $asset = $this->media(['status' => 'processing', 'storage_path' => 'originals/photo.jpg']);
        $inspector = Mockery::mock(MediaInspector::class);
        $inspector->shouldReceive('inspect')->once()->andReturn([
            'mime_type' => 'image/jpeg', 'extension' => 'jpg', 'file_size' => 13,
            'sha256' => str_repeat('d', 64), 'media_type' => 'image', 'width' => 1920,
            'height' => 1080, 'duration_ms' => null, 'video_codec' => null, 'status' => 'ready',
        ]);
        $inspector->shouldReceive('thumbnail')->once()->andReturn(str_repeat('d', 64).'.jpg');

        (new MediaIngestionService(Mockery::mock(UploadCapacityService::class), $inspector))->process($asset->id);

        $asset->refresh();
        $this->assertSame('ready', $asset->status);
        $this->assertSame(1920, $asset->width);
        $this->assertNotNull($asset->processing_completed_at);
    }

    private function media(array $overrides): MediaAsset
    {
        return MediaAsset::create($overrides + [
            'display_name' => 'Contenido',
            'original_filename' => 'content.jpg',
            'storage_path' => 'originals/content.jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'extension' => 'jpg',
            'file_size' => 10,
            'sha256' => str_repeat('c', 64),
        ]);
    }
}
