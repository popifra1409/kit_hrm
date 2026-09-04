<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Employee;
use App\Models\EmployeeAffectation;
use App\Models\Leave;
use App\Observers\EmployeeObserver;
use App\Observers\EmployeeAffectationObserver;
use App\Observers\LeaveObserver;

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
        Leave::observe(LeaveObserver::class);

        // Autoriser l'accès à la doc API (Scramble) en dehors de l'environnement local
        // uniquement pour les super_admin/admin déjà connectés à l'app.
        Gate::define('viewApiDocs', function ($user = null) {
            return $user && $user->hasAnyRole(['super_admin', 'admin']);
        });
    }
}
