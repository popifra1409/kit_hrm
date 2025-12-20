<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Employee;
use App\Models\EmployeeAffectation;
use App\Observers\EmployeeObserver;
use App\Observers\EmployeeAffectationObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        /// Enregistrer les observers
        Employee::observe(EmployeeObserver::class);
        EmployeeAffectation::observe(EmployeeAffectationObserver::class);
    }
}
