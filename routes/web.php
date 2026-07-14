<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\BackupController;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/display', [DisplayController::class, 'display'])->name('display');
Route::get('/preview', [DisplayController::class, 'preview'])->middleware('auth')->name('preview');
Route::get('/media/{mediaAsset}', [MediaController::class, 'stream'])->name('media.stream');
Route::get('/thumbnail/{mediaAsset}', [MediaController::class, 'thumbnail'])->name('media.thumbnail');
Route::get('/admin/media/{mediaAsset}/download', [MediaController::class, 'download'])->middleware('auth')->name('media.download');
Route::get('/api/display/version', [DisplayController::class, 'version']);
Route::get('/api/display/config', [DisplayController::class, 'config']);
Route::get('/api/preview/config', [DisplayController::class, 'previewConfig'])->middleware('auth');
Route::post('/api/display/heartbeat', [DisplayController::class, 'heartbeat']);
Route::post('/api/display/error', [DisplayController::class, 'error']);
Route::get('/admin/backups/{backup}/download', [BackupController::class, 'download'])->middleware('auth')->name('backups.download');
