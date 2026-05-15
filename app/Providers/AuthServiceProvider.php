<?php

namespace App\Providers;

use App\Models\Employee;
use App\Models\Dependent;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\SalaryGrid;
use App\Models\Leave;
use App\Policies\EmployeePolicy;
use App\Policies\DependentPolicy;
use App\Policies\ContractPolicy;
use App\Policies\ContractTypePolicy;
use App\Policies\SalaryGridPolicy;
use App\Policies\LeavePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Employee::class => EmployeePolicy::class,
        Dependent::class => DependentPolicy::class,
        Contract::class => ContractPolicy::class,
        ContractType::class => ContractTypePolicy::class,
        SalaryGrid::class => SalaryGridPolicy::class,
        Leave::class => LeavePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Super Admin bypass - Accès total
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}
