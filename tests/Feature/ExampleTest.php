<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_the_administration_panel(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_admin_login_is_available(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_framework_health_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
