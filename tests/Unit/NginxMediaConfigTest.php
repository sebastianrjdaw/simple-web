<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class NginxMediaConfigTest extends TestCase
{
    public function test_media_locations_take_precedence_over_the_static_asset_regex(): void
    {
        $config = file_get_contents(dirname(__DIR__, 2).'/docker/nginx/default.conf');

        $this->assertIsString($config);
        $this->assertStringContainsString('location ^~ /_protected_media/', $config);
        $this->assertStringContainsString('location ^~ /_protected_thumbnails/', $config);
        $this->assertStringContainsString('location ^~ /storage/', $config);
        $this->assertStringContainsString('alias /data/thumbnails/', $config);
    }
}
