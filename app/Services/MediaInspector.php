<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class MediaInspector
{
    public function inspect(string $path, string $originalName): array
    {
        $absolute = Storage::disk('media')->path($path);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: 'application/octet-stream';
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $base = ['mime_type'=>$mime, 'extension'=>$extension, 'file_size'=>filesize($absolute), 'sha256'=>hash_file('sha256', $absolute)];

        if (in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            $size = getimagesize($absolute);
            if (! $size) throw ValidationException::withMessages(['data.storage_path'=>'La imagen no es válida.']);
            return $base + ['media_type'=>'image','width'=>$size[0],'height'=>$size[1],'duration_ms'=>null,'video_codec'=>null,'status'=>'ready'];
        }
        if ($extension !== 'mp4') throw ValidationException::withMessages(['data.storage_path'=>'Solo se admiten JPEG, PNG, WebP y MP4/H.264.']);
        $result = Process::timeout(30)->run(['ffprobe','-v','error','-print_format','json','-show_format','-show_streams',$absolute]);
        if (! $result->successful()) throw ValidationException::withMessages(['data.storage_path'=>'El MP4 no es válido. Expórtalo como MP4/H.264.']);
        $probe = json_decode($result->output(), true); $video = collect($probe['streams'] ?? [])->firstWhere('codec_type', 'video');
        if (! $video || ($video['codec_name'] ?? '') !== 'h264') throw ValidationException::withMessages(['data.storage_path'=>'El vídeo debe utilizar el códec H.264.']);
        return $base + ['media_type'=>'video','width'=>$video['width'] ?? null,'height'=>$video['height'] ?? null,
            'duration_ms'=>(int) round(((float)($probe['format']['duration'] ?? 0))*1000), 'video_codec'=>'h264','status'=>'ready'];
    }

    public function thumbnail(string $path, string $mediaType, string $hash): ?string
    {
        $source=Storage::disk('media')->path($path); $target=$hash.'.jpg'; $absolute=Storage::disk('thumbnails')->path($target);
        $args=$mediaType==='video' ? ['ffmpeg','-y','-ss','1','-i',$source,'-frames:v','1','-vf','scale=480:-2',$absolute]
            : ['ffmpeg','-y','-i',$source,'-frames:v','1','-vf','scale=480:-2',$absolute];
        return Process::timeout(60)->run($args)->successful() ? $target : null;
    }
}
