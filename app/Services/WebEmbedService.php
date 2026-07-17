<?php

namespace App\Services;

use App\Models\MediaAsset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WebEmbedService
{
    public function validateUrl(string $url): array
    {
        $url = trim($url);
        if (strlen($url) > 2048 || $url === '') {
            throw ValidationException::withMessages(['url' => 'La URL no es válida.']);
        }

        $parts = parse_url($url);
        if (!$parts || strtolower($parts['scheme'] ?? '') !== 'https') {
            throw ValidationException::withMessages(['url' => 'La URL debe empezar por https://.']);
        }

        if (($parts['user'] ?? null) || ($parts['pass'] ?? null)) {
            throw ValidationException::withMessages(['url' => 'La URL no puede incluir usuario ni contraseña.']);
        }

        $host = strtolower($parts['host'] ?? '');
        if (!$host || $this->isPrivateHost($host) || !$this->hostAllowed($host)) {
            throw ValidationException::withMessages(['url' => 'Solo se permiten páginas públicas de AIMHARDER.']);
        }

        $path = $parts['path'] ?? '/';
        $query = isset($parts['query']) ? '?'.$parts['query'] : '';

        return [
            'url' => 'https://'.$host.$path.$query,
            'host' => $host,
        ];
    }

    public function create(array $data): MediaAsset
    {
        $validated = $this->validateUrl($data['url']);
        $options = [
            'refresh_interval_minutes' => (int) ($data['refresh_interval_minutes'] ?? 15),
            'interaction_enabled' => (bool) ($data['interaction_enabled'] ?? false),
            'scroll_mode' => $data['scroll_mode'] ?? 'full',
        ];
        $options['refresh_interval_minutes'] = in_array($options['refresh_interval_minutes'], [0, 5, 15, 30, 60], true) ? $options['refresh_interval_minutes'] : 15;
        $options['scroll_mode'] = in_array($options['scroll_mode'], ['full', 'auto', 'hidden'], true) ? $options['scroll_mode'] : 'full';

        return MediaAsset::create([
            'display_name' => $data['name'] ?: 'WOD del día',
            'original_filename' => $validated['host'],
            'storage_path' => '',
            'thumbnail_path' => null,
            'mime_type' => 'text/html',
            'media_type' => 'web_embed',
            'provider' => 'aimharder',
            'extension' => 'url',
            'file_size' => 0,
            'sha256' => hash('sha256', 'web_embed|aimharder|'.$validated['url'].'|'.Str::random(8)),
            'status' => 'ready',
            'embed_url' => $validated['url'],
            'embed_options_json' => $options,
            'fallback_media_asset_id' => $data['fallback_media_asset_id'] ?? null,
            'validation_status' => 'pending',
            'validation_message' => 'Pendiente de comprobación en navegador.',
        ]);
    }

    public function connectivity(string $url): array
    {
        $validated = $this->validateUrl($url);

        try {
            $response = Http::timeout(8)->withoutRedirecting()->get($validated['url']);
            $blocked = $response->header('x-frame-options') || str_contains(strtolower((string) $response->header('content-security-policy')), 'frame-ancestors');

            return [
                'ok' => $response->successful(),
                'status' => $blocked ? 'blocked_by_provider' : ($response->successful() ? 'available' : 'unavailable'),
                'http_status' => $response->status(),
                'message' => $blocked
                    ? 'AIMHARDER puede estar limitando la incrustación mediante cabeceras de seguridad.'
                    : ($response->successful() ? 'URL válida y con respuesta HTTP.' : 'La URL no respondió correctamente.'),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'status' => 'offline', 'http_status' => null, 'message' => 'No se pudo contactar con la URL.'];
        }
    }

    public function cspFrameSources(): string
    {
        $sources = collect(config('simpleview.web_embed.allowed_hosts', []))
            ->map(fn (string $host) => 'https://'.ltrim($host, '*.'))
            ->merge(['https://*.aimharder.com'])
            ->unique()
            ->implode(' ');

        return "'self' ".$sources;
    }

    private function hostAllowed(string $host): bool
    {
        foreach (config('simpleview.web_embed.allowed_hosts', []) as $allowed) {
            $allowed = strtolower($allowed);
            if ($allowed === $host) {
                return true;
            }
            if (str_starts_with($allowed, '*.')) {
                $root = substr($allowed, 2);
                if ($host !== $root && str_ends_with($host, '.'.$root)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isPrivateHost(string $host): bool
    {
        if (in_array($host, ['localhost', 'localhost.localdomain'], true)) {
            return true;
        }

        $ip = filter_var($host, FILTER_VALIDATE_IP);
        if (!$ip) {
            return false;
        }

        return !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
