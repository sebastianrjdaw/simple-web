<?php

namespace App\Http\Controllers;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;

class MediaController extends Controller
{
    public function stream(MediaAsset $mediaAsset)
    {
        abort_unless($mediaAsset->status === 'ready', 404);
        if (config('simpleview.accel_redirect')) {
            return response('', 200, [
                'X-Accel-Redirect' => $this->internalPath('_protected_media', $mediaAsset->storage_path),
                'Content-Type' => $mediaAsset->mime_type,
                'Cache-Control' => 'public, max-age=31536000, immutable',
            ]);
        }

        return response()->file(Storage::disk('media')->path($mediaAsset->storage_path), [
            'Content-Type' => $mediaAsset->mime_type,
            'Cache-Control' => 'public, max-age=31536000, immutable',
        ]);
    }

    public function thumbnail(MediaAsset $mediaAsset)
    {
        abort_unless($mediaAsset->thumbnail_path, 404);
        if (config('simpleview.accel_redirect')) {
            return response('', 200, [
                'X-Accel-Redirect' => $this->internalPath('_protected_thumbnails', $mediaAsset->thumbnail_path),
                'Cache-Control' => 'public, max-age=86400',
            ]);
        }

        return response()->file(Storage::disk('thumbnails')->path($mediaAsset->thumbnail_path), [
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    public function download(MediaAsset $mediaAsset)
    {
        if (config('simpleview.accel_redirect')) {
            return response('', 200, [
                'X-Accel-Redirect' => $this->internalPath('_protected_media', $mediaAsset->storage_path),
                'Content-Type' => 'application/octet-stream',
                'Content-Disposition' => HeaderUtils::makeDisposition(HeaderUtils::DISPOSITION_ATTACHMENT, $mediaAsset->original_filename, 'download.'.$mediaAsset->extension),
            ]);
        }

        return response()->download(Storage::disk('media')->path($mediaAsset->storage_path), $mediaAsset->original_filename);
    }

    private function internalPath(string $location, string $path): string
    {
        return '/'.$location.'/'.collect(explode('/', trim($path, '/')))->map(fn ($part) => rawurlencode($part))->implode('/');
    }
}
