<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('public.home'))->name('public.home');

// Fallback "login" named route — Laravel's Authenticate middleware uses
// route('login') when it must redirect a guest browser request. Until the
// public B2C auth flow lands (Sprint 5.5 / 13), forward to the Filament
// admin login form so the redirect doesn't 500.
Route::get('/login', fn () => redirect('/admin/login'))->name('login');
