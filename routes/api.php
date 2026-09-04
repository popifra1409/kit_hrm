<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

// ========================================
// AUTHENTIFICATION MOBILE (publique)
// ========================================
Route::prefix('auth')->group(function () {
    Route::post('/activate', [AuthController::class, 'activate']);
    Route::post('/login', [AuthController::class, 'login']);
});

// ========================================
// ROUTES PROTÉGÉES (token Sanctum requis)
// ========================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Les prochains lots (congés, présences, etc.) viendront ici.
});
