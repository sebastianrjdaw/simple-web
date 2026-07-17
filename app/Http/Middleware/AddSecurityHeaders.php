<?php

namespace App\Http\Middleware;

use App\Services\WebEmbedService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $frameSrc = app(WebEmbedService::class)->cspFrameSources();

        $response->headers->set('Content-Security-Policy', "frame-src {$frameSrc};");

        return $response;
    }
}
