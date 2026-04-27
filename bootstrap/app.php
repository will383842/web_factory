<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TenantContext;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sprint 24 — OWASP security headers on every response (web + api).
        $middleware->append(SecurityHeaders::class);

        // Sprint 9 — Multi-tenant audit: tag every API request with the
        // current project_id so KB search, sitemap, internal-links etc.
        // scope automatically.
        $middleware->api(append: [
            TenantContext::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
