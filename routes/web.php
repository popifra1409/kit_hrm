<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PayrollPDFController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/payroll/{payroll}/pdf', [PayrollPDFController::class, 'download'])
        ->name('payroll.pdf');
    
    Route::get('/payroll/{payroll}/view', [PayrollPDFController::class, 'view'])
        ->name('payroll.view');
});