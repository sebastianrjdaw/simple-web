<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RecoveryScriptTest extends TestCase
{
    public function test_recovery_script_preserves_persistent_data_and_checks_critical_features(): void
    {
        $script = file_get_contents(dirname(__DIR__, 2).'/scripts/recover-box.sh');

        $this->assertIsString($script);
        $this->assertStringContainsString('backup_database', $script);
        $this->assertStringContainsString('docker compose down --remove-orphans', $script);
        $this->assertStringNotContainsString('docker compose down -v', $script);
        $this->assertStringNotContainsString('rm -rf "$DATA_DIR"', $script);
        $this->assertStringContainsString('simpleview:health-check', $script);
        $this->assertStringContainsString('visual/aimharder', $script);
        $this->assertStringContainsString('waitForEmbed', $script);
        $this->assertStringContainsString('simple-view-kiosk', $script);
        $this->assertStringContainsString('AutomaticLogin=display', $script);
        $this->assertStringContainsString('start_stack clean', $script);
    }

    public function test_compose_initializes_permissions_and_public_assets_before_app(): void
    {
        $compose = file_get_contents(dirname(__DIR__, 2).'/docker-compose.yml');

        $this->assertIsString($compose);
        $this->assertStringContainsString('init-storage:', $compose);
        $this->assertStringContainsString('cp -a /var/www/html/public/. /persistent-public/', $compose);
        $this->assertStringContainsString('chown -R 33:33 /persistent-data /persistent-public', $compose);
        $this->assertStringContainsString('condition: service_completed_successfully', $compose);
        $this->assertStringContainsString('condition: service_healthy', $compose);
    }
}
