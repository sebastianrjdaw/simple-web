<?php

namespace Tests\Feature;

use App\Models\Layout;
use App\Models\MediaAsset;
use App\Services\PublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoPlaybackOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_player_config_contains_metadata_needed_for_adaptive_buffering(): void
    {
        $video = $this->video();
        $layout = Layout::create(['name' => 'Vídeo grande', 'template_key' => 'full', 'state' => 'draft']);
        $zone = $layout->zones()->create(['zone_key' => 'zone_1', 'position' => 1]);
        $zone->items()->create(['media_asset_id' => $video->id, 'sort_order' => 0]);

        $item = app(PublicationService::class)->config($layout)['zones'][0]['items'][0];

        $this->assertSame(2_147_483_648, $item['size']);
        $this->assertSame(7_200_000, $item['duration_ms']);
        $this->assertSame('video', $item['type']);
    }

    public function test_display_player_prebuffers_and_loops_a_single_video(): void
    {
        $this->get('/display')
            ->assertOk()
            ->assertSee('waitForVideo', false)
            ->assertSee("node.preload='auto'", false)
            ->assertSee('node.loop=z.items.length===1', false)
            ->assertSee('bufferedAhead', false);
    }

    public function test_accelerated_media_response_is_inline_and_supports_byte_ranges(): void
    {
        config(['simpleview.accel_redirect' => true]);
        $video = $this->video();

        $this->get(route('media.stream', $video))
            ->assertOk()
            ->assertHeader('X-Accel-Redirect', '/_protected_media/originals/video-grande.mp4')
            ->assertHeader('Accept-Ranges', 'bytes')
            ->assertHeader('Content-Type', 'video/mp4')
            ->assertHeader('Content-Disposition', "inline; filename=media.mp4; filename*=utf-8''video-grande.mp4");
    }

    private function video(): MediaAsset
    {
        return MediaAsset::create([
            'display_name' => 'Vídeo grande',
            'original_filename' => 'video-grande.mp4',
            'storage_path' => 'originals/video-grande.mp4',
            'mime_type' => 'video/mp4',
            'media_type' => 'video',
            'extension' => 'mp4',
            'file_size' => 2_147_483_648,
            'sha256' => str_repeat('9', 64),
            'duration_ms' => 7_200_000,
            'video_codec' => 'h264',
            'status' => 'ready',
        ]);
    }
}
