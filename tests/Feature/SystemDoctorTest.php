<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SystemDoctorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SystemDoctorTest extends TestCase
{
    use RefreshDatabase;

    private string $doctorData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->doctorData = storage_path('framework/testing/system-doctor');
        foreach (['database', 'media', 'thumbnails', 'backups', 'logs', 'cache', 'metrics', 'temp'] as $directory) {
            File::ensureDirectoryExists($this->doctorData.'/'.$directory);
        }
        config(['simpleview.data_path' => $this->doctorData, 'simpleview.storage.data_path' => $this->doctorData]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->doctorData);
        parent::tearDown();
    }

    public function test_doctor_page_and_repair_require_authentication(): void
    {
        $this->get(route('system-doctor.index'))->assertRedirect();
        $this->post(route('system-doctor.repair'))->assertRedirect();
    }

    public function test_authenticated_user_can_run_check_and_safe_repair(): void
    {
        $user = User::factory()->create();
        $result = app(SystemDoctorService::class)->run(false);

        $this->assertTrue($result['ok']);
        $this->assertGreaterThanOrEqual(10, $result['summary']['checks']);
        $this->actingAs($user)->get(route('system-doctor.index'))->assertOk()->assertSee('Diagnóstico y reparación');
        $this->actingAs($user)->post(route('system-doctor.repair'))->assertOk()->assertSee('Comprobación y reparación terminadas');
        $this->assertDatabaseHas('admin_activity_events', ['user_id' => $user->id, 'action' => 'system.doctor']);
    }
}
