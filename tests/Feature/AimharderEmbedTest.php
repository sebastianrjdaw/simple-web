<?php

namespace Tests\Feature;

use App\Models\Layout;
use App\Models\MediaAsset;
use App\Models\User;
use App\Services\PublicationService;
use App\Services\WebEmbedService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AimharderEmbedTest extends TestCase
{
    use RefreshDatabase;

    public function test_aimharder_url_validation_accepts_https_subdomains(): void
    {
        $result = app(WebEmbedService::class)->validateUrl('https://gamancrossfit.aimharder.com/navwod');

        $this->assertSame('https://gamancrossfit.aimharder.com/navwod', $result['url']);
        $this->assertSame('gamancrossfit.aimharder.com', $result['host']);
    }

    public function test_aimharder_url_validation_rejects_unsafe_urls(): void
    {
        foreach (['http://gamancrossfit.aimharder.com/navwod', 'javascript:alert(1)', 'https://localhost/navwod', 'https://example.com/navwod', 'https://user:pass@gamancrossfit.aimharder.com/navwod'] as $url) {
            try {
                app(WebEmbedService::class)->validateUrl($url);
                $this->fail("URL should have been rejected: {$url}");
            } catch (ValidationException) {
                $this->assertTrue(true);
            }
        }
    }

    public function test_visual_editor_can_create_web_embed_and_assign_to_empty_zone(): void
    {
        $user = User::factory()->create();
        $layout = $this->layout();

        $response = $this->actingAs($user)->postJson(route('visual-editor.web-embed', $layout), [
            'name' => 'WOD del día',
            'url' => 'https://gamancrossfit.aimharder.com/navwod',
            'refresh_interval_minutes' => 15,
            'interaction_enabled' => false,
            'scroll_mode' => 'full',
        ])->assertOk();

        $mediaId = $response->json('item.id');
        $this->actingAs($user)->patchJson(route('visual-editor.save', $layout), [
            'name' => 'Pantalla',
            'template_key' => 'full',
            'zones' => [[
                'key' => 'zone_1',
                'image_fit_default' => 'cover',
                'image_duration_default_ms' => 10000,
                'transition_type' => 'fade',
                'transition_duration_ms' => 500,
                'items' => [['media_asset_id' => $mediaId]],
            ]],
        ])->assertOk();

        $this->assertDatabaseHas('playlist_items', ['media_asset_id' => $mediaId]);
    }

    public function test_web_embed_cannot_be_mixed_with_media_in_same_zone(): void
    {
        Storage::fake('media');
        $user = User::factory()->create();
        $layout = $this->layout();
        $image = $this->image();
        $embed = app(WebEmbedService::class)->create([
            'name' => 'WOD del día',
            'url' => 'https://gamancrossfit.aimharder.com/navwod',
            'refresh_interval_minutes' => 15,
            'interaction_enabled' => false,
            'scroll_mode' => 'full',
        ]);

        $this->actingAs($user)->patchJson(route('visual-editor.save', $layout), [
            'name' => 'Pantalla',
            'template_key' => 'full',
            'zones' => [[
                'key' => 'zone_1',
                'image_fit_default' => 'cover',
                'image_duration_default_ms' => 10000,
                'transition_type' => 'fade',
                'transition_duration_ms' => 500,
                'items' => [['media_asset_id' => $image->id], ['media_asset_id' => $embed->id]],
            ]],
        ])->assertStatus(422);
    }

    public function test_published_config_renders_web_embed_for_display_and_preview(): void
    {
        $user = User::factory()->create();
        $layout = $this->layout();
        $embed = app(WebEmbedService::class)->create([
            'name' => 'WOD del día',
            'url' => 'https://gamancrossfit.aimharder.com/navwod',
            'refresh_interval_minutes' => 15,
            'interaction_enabled' => false,
            'scroll_mode' => 'full',
        ]);
        $layout->zones()->first()->items()->create(['media_asset_id' => $embed->id, 'sort_order' => 0]);

        $published = app(PublicationService::class)->publish($layout, $user->id);

        $this->assertSame('web_embed', $published->snapshot_json['zones'][0]['items'][0]['type']);
        $this->assertSame('https://gamancrossfit.aimharder.com/navwod', $published->snapshot_json['zones'][0]['items'][0]['url']);
        $this->get('/display')->assertOk()->assertSee("iframe.sandbox='allow-scripts allow-same-origin allow-forms'", false);
        $this->actingAs($user)->get(route('web-embeds.preview', ['url' => 'https://gamancrossfit.aimharder.com/navwod', 'name' => 'WOD del día']))->assertOk()->assertSee('<iframe', false)->assertSee('sandbox="allow-scripts allow-same-origin allow-forms"', false);
        $this->get('/display')->assertHeader('Content-Security-Policy', "frame-src 'self' https://aimharder.com https://*.aimharder.com;");
    }

    public function test_active_web_embed_deletion_is_blocked(): void
    {
        $user = User::factory()->create();
        $layout = $this->layout();
        $embed = app(WebEmbedService::class)->create([
            'name' => 'WOD del día',
            'url' => 'https://gamancrossfit.aimharder.com/navwod',
            'refresh_interval_minutes' => 15,
            'interaction_enabled' => false,
            'scroll_mode' => 'full',
        ]);
        $layout->zones()->first()->items()->create(['media_asset_id' => $embed->id, 'sort_order' => 0]);
        app(PublicationService::class)->publish($layout, $user->id);

        $result = app(\App\Services\MediaDeletionService::class)->delete($embed, $user->id);

        $this->assertFalse($result['deleted']);
        $this->assertSame('blocked', $result['status']);
    }

    private function layout(): Layout
    {
        $layout = Layout::create(['name' => 'Pantalla', 'template_key' => 'full', 'state' => 'draft']);
        $layout->zones()->create(['zone_key' => 'zone_1', 'position' => 1]);
        return $layout;
    }

    private function image(): MediaAsset
    {
        Storage::disk('media')->put('image.jpg', 'image');
        return MediaAsset::create([
            'display_name' => 'Imagen',
            'original_filename' => 'image.jpg',
            'storage_path' => 'image.jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'extension' => 'jpg',
            'file_size' => 10,
            'sha256' => str_repeat('1', 64),
            'status' => 'ready',
        ]);
    }
}
