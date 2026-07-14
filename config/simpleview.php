<?php

return [
    'admin_username' => env('SIMPLEVIEW_ADMIN_USERNAME', 'admin'),
    'admin_email' => env('SIMPLEVIEW_ADMIN_EMAIL', 'admin@simpleview.local'),
    'admin_password' => env('SIMPLEVIEW_ADMIN_PASSWORD', 'admin123'),
    'force_password_change' => env('SIMPLEVIEW_FORCE_PASSWORD_CHANGE', false),
    'storage_reserve_gb' => (int) env('SIMPLEVIEW_STORAGE_RESERVE_GB', 15),
    'max_upload_mb' => (int) env('SIMPLEVIEW_MAX_UPLOAD_MB', 2048),
    'max_upload_hard_mb' => (int) env('SIMPLEVIEW_MAX_UPLOAD_HARD_MB', 4096),
    'data_path' => env('SIMPLEVIEW_DATA_PATH', '/data'),
    'visual_editor_enabled' => env('SIMPLEVIEW_VISUAL_EDITOR_ENABLED', true),
    'storage' => [
        'data_path' => env('SIMPLEVIEW_DATA_PATH', '/data'),
        'warning_percent' => (float) env('SIMPLEVIEW_STORAGE_WARNING_PERCENT', 80),
        'block_percent' => (float) env('SIMPLEVIEW_STORAGE_BLOCK_PERCENT', 90),
        // This technical floor cannot be reduced accidentally through the UI/environment.
        'reserve_bytes' => max(15, (float) env('SIMPLEVIEW_STORAGE_RESERVE_GB', 15)) * 1024 ** 3,
        'warning_free_bytes' => max(20, (float) env('SIMPLEVIEW_STORAGE_WARNING_FREE_GB', 20)) * 1024 ** 3,
        'host_report_max_age_minutes' => (int) env('SIMPLEVIEW_STORAGE_HOST_REPORT_MAX_AGE_MINUTES', 15),
        'scan_interval_minutes' => (int) env('SIMPLEVIEW_STORAGE_SCAN_INTERVAL_MINUTES', 5),
        'deep_scan_hour' => (int) env('SIMPLEVIEW_STORAGE_DEEP_SCAN_HOUR', 3),
        'temp_retention_hours' => (int) env('SIMPLEVIEW_TEMP_RETENTION_HOURS', 24),
        'log_retention_days' => (int) env('SIMPLEVIEW_LOG_RETENTION_DAYS', 14),
        'upload_multiplier' => (float) env('SIMPLEVIEW_UPLOAD_SPACE_MULTIPLIER', 2.1),
        'host_report_path' => env('SIMPLEVIEW_STORAGE_HOST_REPORT_PATH', '/data/metrics/storage-host.json'),
    ],
];
