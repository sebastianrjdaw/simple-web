<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\StorageController;
use App\Http\Controllers\SystemDoctorController;
use App\Http\Controllers\VisualEditorController;
use App\Models\Layout;
use Illuminate\Support\Facades\Route;

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
Route::middleware('auth')->group(function () {
    Route::get('/admin/layouts/{layout}/edit', fn (Layout $layout) => redirect()->route('visual-editor', $layout)->with('status', 'Se ha abierto el editor visual.'))->name('layouts.classic.redirect');
    Route::get('/admin/layouts/{layout}/visual', [VisualEditorController::class, 'show'])->name('visual-editor');
    Route::get('/admin/layouts/{layout}/visual/state', [VisualEditorController::class, 'state'])->name('visual-editor.state');
    Route::patch('/admin/layouts/{layout}/visual', [VisualEditorController::class, 'save'])->name('visual-editor.save');
    Route::post('/admin/layouts/{layout}/visual/upload', [VisualEditorController::class, 'upload'])->name('visual-editor.upload');
    Route::post('/admin/layouts/{layout}/visual/aimharder', [VisualEditorController::class, 'addAimHarder'])->name('visual-editor.aimharder');
    Route::post('/admin/layouts/{layout}/visual/processing-status', [VisualEditorController::class, 'processingStatus'])->name('visual-editor.processing-status');
    Route::get('/admin/layouts/{layout}/visual/media/{media}/uses', [VisualEditorController::class, 'mediaUses'])->name('visual-editor.media-uses');
    Route::delete('/admin/layouts/{layout}/visual/media/{media}', [VisualEditorController::class, 'deleteMedia'])->name('visual-editor.media-delete');
    Route::post('/admin/layouts/{layout}/visual/publish', [VisualEditorController::class, 'publish'])->name('visual-editor.publish');
    Route::post('/admin/storage/preflight', [VisualEditorController::class, 'preflight'])->name('storage.preflight');
    Route::get('/admin/storage', [StorageController::class, 'index'])->name('storage.index');
    Route::post('/admin/storage/refresh', [StorageController::class, 'refresh'])->name('storage.refresh');
    Route::delete('/admin/storage/unused', [StorageController::class, 'cleanup'])->name('storage.cleanup');
    Route::delete('/admin/storage/layouts', [StorageController::class, 'deleteLayouts'])->name('storage.layouts.cleanup');
    Route::get('/admin/system/doctor', [SystemDoctorController::class, 'index'])->name('system-doctor.index');
    Route::post('/admin/system/doctor', [SystemDoctorController::class, 'repair'])->middleware('throttle:3,1')->name('system-doctor.repair');
});
