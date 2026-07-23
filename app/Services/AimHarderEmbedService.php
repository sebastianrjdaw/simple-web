<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Validation\ValidationException;

class AimHarderEmbedService
{
    public function create(string $content, ?string $name = null): MediaAsset
    {
        $url = $this->normalize($content);
        $hash = hash('sha256', 'aimharder:'.$url);
        $host = parse_url($url, PHP_URL_HOST);

        $attributes = [
            'display_name' => trim((string) $name) ?: 'AimHarder · '.$host,
            'original_filename' => 'aimharder.html',
            'storage_path' => '',
            'mime_type' => 'text/html',
            'media_type' => 'embed',
            'external_provider' => 'aimharder',
            'external_url' => $url,
            'extension' => 'url',
            'file_size' => 0,
            'status' => 'ready',
        ];
        $asset = MediaAsset::withTrashed()->where('sha256', $hash)->first();

        if ($asset) {
            $asset->restore();
            $asset->update($attributes);

            return $asset;
        }

        return MediaAsset::create(['sha256' => $hash] + $attributes);
    }

    public function normalize(string $content): string
    {
        $content = trim($content);
        if (preg_match('/<iframe\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/is', $content, $matches)) {
            $content = html_entity_decode($matches[2], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $url = filter_var(trim($content), FILTER_VALIDATE_URL);
        $parts = $url ? parse_url($url) : false;
        $host = strtolower((string) ($parts['host'] ?? ''));
        $allowedHost = $host === 'aimharder.com' || str_ends_with($host, '.aimharder.com');

        if (! $url || ($parts['scheme'] ?? null) !== 'https' || ! $allowedHost || isset($parts['user']) || isset($parts['pass']) || (($parts['port'] ?? 443) !== 443)) {
            throw ValidationException::withMessages([
                'content' => 'Introduce una URL HTTPS o un iframe oficial de AimHarder.',
            ]);
        }

        if (($parts['path'] ?? '/') === '/') {
            throw ValidationException::withMessages([
                'content' => 'La portada de AimHarder bloquea los iframes. Usa la URL del contenido concreto, por ejemplo /schedule.',
            ]);
        }

        return $url;
    }
}
