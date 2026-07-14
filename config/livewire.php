<?php

$config = require base_path('vendor/livewire/livewire/config/livewire.php');
$functional = (int) env('SIMPLEVIEW_MAX_UPLOAD_MB', 2048);
$hard = max(1, (int) env('SIMPLEVIEW_MAX_UPLOAD_HARD_MB', 4096));
$effective = $functional <= 0 ? $hard : min($functional, $hard);

$config['temporary_file_upload'] = array_replace(
    $config['temporary_file_upload'],
    [
        'disk' => 'media',
        'rules' => ['required', 'file', 'max:'.($effective * 1024)],
        'directory' => 'livewire-tmp',
        'max_upload_time' => 60,
    ],
);

return $config;
