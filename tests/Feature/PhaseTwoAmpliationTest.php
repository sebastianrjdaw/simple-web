<?php

namespace Tests\Feature;

use App\Models\Layout;
use App\Models\MediaAsset;
use App\Models\Setting;
use App\Models\User;
use App\Services\BackupScheduleService;
use App\Services\LayoutDeletionService;
use App\Services\MediaDeletionService;
use App\Services\PublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhaseTwoAmpliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_media_can_be_deleted_safely(): void
    {
        Storage::fake('media');
        Storage::fake('thumbnails');
        Storage::disk('media')->put('originals/a.jpg', 'image');
        Storage::disk('thumbnails')->put('a.jpg', 'thumb');

        $asset = $this->media(['storage_path' => 'originals/a.jpg', 'thumbnail_path' => 'a.jpg']);
        $result = app(MediaDeletionService::class)->delete($asset, User::factory()->create()->id);

        $this->assertTrue($result['deleted']);
        $this->assertSoftDeleted('media_assets', ['id' => $asset->id]);
        Storage::disk('media')->assertMissing('originals/a.jpg');
        Storage::disk('thumbnails')->assertMissing('a.jpg');
    }

    public function test_active_publication_media_is_blocked_from_deletion(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('photo.jpg', 'image');

        $user = User::factory()->create();
        $asset = $this->media(['storage_path' => 'photo.jpg']);
        $layout = $this->layoutWithMedia($asset);
        app(PublicationService::class)->publish($layout, $user->id);

        $result = app(MediaDeletionService::class)->delete($asset, $user->id);

        $this->assertFalse($result['deleted']);
        $this->assertSame('blocked', $result['status']);
        $this->assertDatabaseHas('media_assets', ['id' => $asset->id, 'deleted_at' => null]);
        Storage::disk('media')->assertExists('photo.jpg');
    }

    public function test_fallback_media_is_blocked_from_deletion(): void
    {
        $asset = $this->media();
        Setting::updateOrCreate(['key' => 'fallback_media_asset_id'], ['value' => (string) $asset->id, 'type' => 'string']);

        $result = app(MediaDeletionService::class)->delete($asset, User::factory()->create()->id);

        $this->assertFalse($result['deleted']);
        $this->assertContains('configuration', $result['blocked_reasons']);
    }

    public function test_media_used_only_in_inactive_designs_can_be_detached_and_deleted(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('inactive.jpg', 'image');

        $asset = $this->media(['storage_path' => 'inactive.jpg']);
        $this->layoutWithMedia($asset);

        $result = app(MediaDeletionService::class)->delete($asset, User::factory()->create()->id, true);

        $this->assertTrue($result['deleted']);
        $this->assertDatabaseMissing('playlist_items', ['media_asset_id' => $asset->id]);
        $this->assertSoftDeleted('media_assets', ['id' => $asset->id]);
    }

    public function test_inactive_layout_can_be_deleted_without_deleting_media(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('kept.jpg', 'image');

        $asset = $this->media(['storage_path' => 'kept.jpg']);
        $layout = $this->layoutWithMedia($asset);
        Layout::create(['name' => 'Otro', 'template_key' => 'full', 'state' => 'draft']);

        $result = app(LayoutDeletionService::class)->delete($layout, User::factory()->create()->id);

        $this->assertTrue($result['deleted']);
        $this->assertDatabaseMissing('layouts', ['id' => $layout->id]);
        $this->assertDatabaseHas('media_assets', ['id' => $asset->id, 'deleted_at' => null]);
        Storage::disk('media')->assertExists('kept.jpg');
    }

    public function test_active_layout_and_classic_edit_route_are_protected(): void
    {
        Storage::fake('media');
        Storage::disk('media')->put('active.jpg', 'image');

        $user = User::factory()->create();
        $asset = $this->media(['storage_path' => 'active.jpg']);
        $layout = $this->layoutWithMedia($asset);
        $published = app(PublicationService::class)->publish($layout, $user->id);

        $result = app(LayoutDeletionService::class)->delete($published, $user->id);

        $this->assertFalse($result['deleted']);
        $this->actingAs($user)->get('/admin/layouts/'.$layout->id.'/edit')->assertRedirect(route('visual-editor', $layout));
    }

    public function test_backup_frequency_is_capped_at_two_days(): void
    {
        Setting::updateOrCreate(['key' => 'backup_frequency_days'], ['value' => '7', 'type' => 'string']);

        $this->assertSame(2, app(BackupScheduleService::class)->frequencyDays());
    }

    private function media(array $overrides = []): MediaAsset
    {
        return MediaAsset::create($overrides + [
            'display_name' => 'Contenido',
            'original_filename' => 'content.jpg',
            'storage_path' => 'content.jpg',
            'mime_type' => 'image/jpeg',
            'media_type' => 'image',
            'extension' => 'jpg',
            'file_size' => 10,
            'sha256' => str_repeat((string) random_int(0, 9), 64),
            'status' => 'ready',
        ]);
    }

    private function layoutWithMedia(MediaAsset $asset): Layout
    {
        $layout = Layout::create(['name' => 'Diseño', 'template_key' => 'full', 'state' => 'draft']);
        $zone = $layout->zones()->create(['zone_key' => 'zone_1', 'position' => 1]);
        $zone->items()->create(['media_asset_id' => $asset->id, 'sort_order' => 0]);
        return $layout;
    }
}
