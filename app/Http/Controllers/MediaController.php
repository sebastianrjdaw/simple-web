<?php
namespace App\Http\Controllers;
use App\Models\MediaAsset;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
class MediaController extends Controller {
 public function stream(MediaAsset $mediaAsset):BinaryFileResponse {abort_unless($mediaAsset->status==='ready',404);return response()->file(Storage::disk('media')->path($mediaAsset->storage_path),['Content-Type'=>$mediaAsset->mime_type,'Cache-Control'=>'public, max-age=31536000, immutable']);}
 public function thumbnail(MediaAsset $mediaAsset):BinaryFileResponse {abort_unless($mediaAsset->thumbnail_path,404);return response()->file(Storage::disk('thumbnails')->path($mediaAsset->thumbnail_path),['Cache-Control'=>'public, max-age=86400']);}
 public function download(MediaAsset $mediaAsset):BinaryFileResponse {return response()->download(Storage::disk('media')->path($mediaAsset->storage_path),$mediaAsset->original_filename);}
}
