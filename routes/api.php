<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ProjectController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('/user', fn (Request $request) => $request->user());

    Route::prefix('v1')->name('api.v1.')->group(function (): void {
        Route::apiResource('projects', ProjectController::class)
            ->only(['index', 'show', 'store', 'destroy']);
    });
});
