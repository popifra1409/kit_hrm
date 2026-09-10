<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\EmployeeProfileController;
use App\Http\Controllers\Api\DependentController;
use App\Http\Controllers\Api\DiplomaController;
use App\Http\Controllers\Api\LeaveController;
use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\AppInfoController;
use Illuminate\Support\Facades\Route;

// ========================================
// PUBLIC (aucune authentification requise)
// ========================================
Route::get('/app-info', [AppInfoController::class, 'show']);

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
    Route::put('/auth/password', [AuthController::class, 'changePassword']);

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

    // Messagerie interne
    Route::prefix('chat')->group(function () {
        Route::get('/conversations', [ConversationController::class, 'index']);
        Route::post('/conversations/direct', [ConversationController::class, 'startDirect']);
        Route::post('/conversations/group', [ConversationController::class, 'createGroup']);
        Route::post('/conversations/{id}/members', [ConversationController::class, 'addMembers']);
        Route::post('/conversations/{id}/leave', [ConversationController::class, 'leaveGroup']);

        Route::get('/conversations/{conversationId}/messages', [MessageController::class, 'index']);
        Route::post('/conversations/{conversationId}/messages', [MessageController::class, 'store']);
        Route::delete('/conversations/{conversationId}/messages/{messageId}', [MessageController::class, 'destroy']);
    });

    // Les prochains lots viendront ici.
});
