<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;

class StorageReconciliationService
{
    public function run(): array
    {
        $registered = MediaAsset::withTrashed()->pluck('storage_path')->all();
        $physical = Storage::disk('media')->allFiles();
        $missing = MediaAsset::whereNotIn('storage_path', $physical)->pluck('storage_path', 'id')->all();
        MediaAsset::whereIn('storage_path', $physical)->update(['storage_verified_at' => now()]);
        return ['orphan_media' => array_values(array_diff($physical, $registered)), 'missing_media' => $missing,
            'orphan_thumbnails' => array_values(array_diff(Storage::disk('thumbnails')->allFiles(), MediaAsset::whereNotNull('thumbnail_path')->pluck('thumbnail_path')->all()))];
    }
}
