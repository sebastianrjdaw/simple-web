<?php

namespace Tests\Feature;

use App\Models\Layout;
use App\Models\User;
use App\Services\AimHarderEmbedService;
use App\Services\PublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AimHarderEmbedTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_accepts_an_aimharder_url_or_official_iframe_code(): void
    {
        $service = app(AimHarderEmbedService::class);

        $this->assertSame(
            'https://mybox.aimharder.com/schedule',
            $service->normalize('https://mybox.aimharder.com/schedule'),
        );
        $this->assertSame(
            'https://mybox.aimharder.com/schedule?day=1',
            $service->normalize('<iframe src="https://mybox.aimharder.com/schedule?day=1" width="100%"></iframe>'),
        );
    }

    public function test_it_rejects_non_aimharder_or_non_embeddable_home_urls(): void
    {
        $service = app(AimHarderEmbedService::class);

        foreach (['https://aimharder.com.evil.test/schedule', 'http://mybox.aimharder.com/schedule', 'https://aimharder.com/'] as $url) {
            try {
                $service->normalize($url);
                $this->fail("La URL {$url} debería haberse rechazado.");
            } catch (ValidationException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_editor_can_create_reusable_aimharder_content(): void
    {
        $user = User::factory()->create();
        $layout = Layout::create(['name' => 'Box', 'template_key' => 'full', 'state' => 'draft']);
        $layout->zones()->create(['zone_key' => 'zone_1', 'position' => 1]);

        $response = $this->actingAs($user)->postJson(route('visual-editor.aimharder', $layout), [
            'name' => 'Horario del box',
            'content' => '<iframe src="https://wood.aimharder.com/schedule"></iframe>',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('media.type', 'embed')
            ->assertJsonPath('media.provider', 'aimharder')
            ->assertJsonPath('media.external_url', 'https://wood.aimharder.com/schedule')
            ->assertJsonPath('media.default_duration_ms', 300000);
        $this->assertDatabaseHas('media_assets', [
            'display_name' => 'Horario del box',
            'media_type' => 'embed',
            'external_provider' => 'aimharder',
            'external_url' => 'https://wood.aimharder.com/schedule',
            'file_size' => 0,
            'status' => 'ready',
        ]);
    }

    public function test_aimharder_content_can_be_published_without_a_local_file(): void
    {
        Storage::fake('media');
        $user = User::factory()->create();
        $embed = app(AimHarderEmbedService::class)->create('https://wood.aimharder.com/schedule', 'WOD y horarios');
        $layout = Layout::create(['name' => 'Box', 'template_key' => 'full', 'state' => 'draft']);
        $zone = $layout->zones()->create(['zone_key' => 'zone_1', 'position' => 1]);
        $zone->items()->create(['media_asset_id' => $embed->id, 'sort_order' => 0, 'image_duration_ms' => 600000]);

        $published = app(PublicationService::class)->publish($layout, $user->id);
        $item = $published->snapshot_json['zones'][0]['items'][0];

        $this->assertSame('embed', $item['type']);
        $this->assertSame('aimharder', $item['provider']);
        $this->assertSame('https://wood.aimharder.com/schedule', $item['url']);
        $this->assertSame(600000, $item['duration_ms']);
    }

    public function test_display_player_uses_a_sandboxed_iframe(): void
    {
        $this->get('/display')
            ->assertOk()
            ->assertSee("item.type==='embed'?'iframe'", false)
            ->assertSee('allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox', false)
            ->assertSee('waitForEmbed', false);
    }
}
