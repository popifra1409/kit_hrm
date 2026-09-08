<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayrollPDFController;
use App\Http\Controllers\ClearCacheController;
use App\Http\Controllers\EmployeeProfessionalProfileController;

Route::get('/', function () {
    return view('welcome-hrm');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/payroll/{payroll}/pdf', [PayrollPDFController::class, 'download'])
        ->name('payroll.pdf');

    Route::get('/payroll/{payroll}/view', [PayrollPDFController::class, 'view'])
        ->name('payroll.view');

    Route::get('/admin/employees/{employee}/profile', [EmployeeProfessionalProfileController::class, 'download'])
        ->name('employees.profile.download');

    Route::get('/admin/employees/{employee}/profile-preview', [EmployeeProfessionalProfileController::class, 'preview'])
        ->name('employees.profile.preview');
});

Route::middleware(['web', 'auth'])->group(function () {
    // Route cache - seulement admin et drh
    Route::get('/clear-cache', ClearCacheController::class)
        ->name('clear-cache')
        ->middleware('can:manage_cache');
});
