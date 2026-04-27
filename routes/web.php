<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('public.home'))->name('public.home');

// Sprint 23 — PWA static endpoints (served by nginx in prod, by route in tests/local)
Route::get('/manifest.webmanifest', function () {
    return response(file_get_contents(public_path('manifest.webmanifest')), 200, [
        'Content-Type' => 'application/manifest+json',
    ]);
});
Route::get('/sw.js', function () {
    return response(file_get_contents(public_path('sw.js')), 200, [
        'Content-Type' => 'application/javascript',
    ]);
});

// Fallback "login" named route — Laravel's Authenticate middleware uses
// route('login') when it must redirect a guest browser request. Until the
// public B2C auth flow lands (Sprint 5.5 / 13), forward to the Filament
// admin login form so the redirect doesn't 500.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
