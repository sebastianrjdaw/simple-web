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
];
