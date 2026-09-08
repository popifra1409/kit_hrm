<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeProfileController;
use App\Http\Controllers\Api\DependentController;
use App\Http\Controllers\Api\DiplomaController;
use App\Http\Controllers\Api\LeaveController;
use Illuminate\Support\Facades\Route;

// ========================================
// AUTHENTIFICATION MOBILE (publique)
// ========================================
Route::prefix('auth')->group(function () {
    Route::post('/activate', [AuthController::class, 'activate']);
    Route::post('/login', [AuthController::class, 'login']); // matricule + password
});

// ========================================
// ROUTES PROTÉGÉES (token Sanctum requis)
// ========================================
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::prefix('employee')->group(function () {
        // Profil
        Route::get('/profile', [EmployeeProfileController::class, 'show']);
        Route::put('/profile', [EmployeeProfileController::class, 'update']);
        Route::post('/profile/photo', [EmployeeProfileController::class, 'updatePhoto']);

        // Ayants droit
        Route::get('/dependents', [DependentController::class, 'index']);
        Route::post('/dependents', [DependentController::class, 'store']);
        Route::get('/dependents/{id}', [DependentController::class, 'show']);
        Route::post('/dependents/{id}', [DependentController::class, 'update']);
        Route::delete('/dependents/{id}', [DependentController::class, 'destroy']);

        // Diplômes & Formations
        Route::get('/diplomas', [DiplomaController::class, 'index']);
        Route::post('/diplomas', [DiplomaController::class, 'store']);
        Route::post('/diplomas/{id}', [DiplomaController::class, 'update']);
        Route::delete('/diplomas/{id}', [DiplomaController::class, 'destroy']);

        // Congés & Permissions
        Route::get('/leave-types', [LeaveController::class, 'types']);
        Route::get('/leaves/balance', [LeaveController::class, 'balance']);
        Route::get('/leaves', [LeaveController::class, 'index']);
        Route::post('/leaves', [LeaveController::class, 'store']);
        Route::get('/leaves/{id}', [LeaveController::class, 'show']);
    });

    // Les prochains lots viendront ici.
});
