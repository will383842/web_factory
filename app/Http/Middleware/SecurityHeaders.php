<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sprint 24 — OWASP-recommended security headers on every HTTP response.
 *
 * CSP is permissive by default (`'self'` + Bunny Fonts) so the WebFactory
 * marketing site renders. Per-tenant generated sites override CSP via their
 * own brief at deploy time.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        if (! $response->headers->has('Content-Security-Policy')) {
            // `'unsafe-eval'` is required by Alpine.js (used by Filament admin
            // through Livewire) which compiles directives like `x-on:click="…"`
            // into AsyncFunction at runtime. Removing it breaks the entire
            // admin panel. Alpine ships an alternate CSP-safe build
            // (`@alpinejs/csp`) but Filament v4 does not use it.
            //
            // `connect-src` must include S3-compatible storage origins so
            // Livewire's FileUpload pre-signed PUT direct-upload works
            // (Filament FileUpload uses this in production). We pull the
            // configured AWS_ENDPOINT (MinIO in dev, S3 in prod) from
            // config to avoid hardcoding hostnames.
            $extraConnectSrc = [];
            $awsEndpoint = (string) config('filesystems.disks.s3.endpoint', '');
            if ($awsEndpoint !== '') {
                $extraConnectSrc[] = $awsEndpoint;
            }
            // Allow common S3 / R2 production hosts even when endpoint is empty.
            $extraConnectSrc[] = 'https://*.amazonaws.com';
            $extraConnectSrc[] = 'https://*.r2.cloudflarestorage.com';
            $extraConnectSrc[] = 'https://*.b2.backblazeb2.com';

            $response->headers->set('Content-Security-Policy', implode('; ', [
                "default-src 'self'",
                "img-src 'self' data: blob: https:",
                "style-src 'self' 'unsafe-inline' https://fonts.bunny.net",
                "font-src 'self' https://fonts.bunny.net",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'",
                "connect-src 'self' ws: wss: blob: data: ".implode(' ', $extraConnectSrc),
                "media-src 'self' blob:",
                "frame-ancestors 'none'",
            ]));
        }

        return $response;
    }
}
