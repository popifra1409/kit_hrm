<?php

namespace App\Providers;

use App\Models\User;
use App\Models\Employee;
use App\Models\Dependent;
use App\Models\Contract;
use App\Models\ContractType;
use App\Models\SalaryGrid;
use App\Models\Leave;
use App\Models\Document;
use App\Models\PerformanceEvaluation; 
use App\Models\Training;
use App\Models\Department;
use App\Models\Service;
use App\Models\Position;

use App\Policies\UserPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\DependentPolicy;
use App\Policies\ContractPolicy;
use App\Policies\ContractTypePolicy;
use App\Policies\SalaryGridPolicy;
use App\Policies\LeavePolicy;
use App\Policies\DocumentPolicy;
use App\Policies\PerformanceEvaluationPolicy;
use App\Policies\TrainingPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\ServicePolicy;
use App\Policies\PositionPolicy;

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
        User::class => UserPolicy::class,
        Employee::class => EmployeePolicy::class,
        Dependent::class => DependentPolicy::class,
        Contract::class => ContractPolicy::class,
        ContractType::class => ContractTypePolicy::class,
        SalaryGrid::class => SalaryGridPolicy::class,
        Leave::class => LeavePolicy::class,
        Document::class => DocumentPolicy::class,
        PerformanceEvaluation::class => PerformanceEvaluationPolicy::class,
        Training::class => TrainingPolicy::class,
        Department::class => DepartmentPolicy::class,
        Service::class => ServicePolicy::class,
        Position::class => PositionPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // ✅ Super Admin bypass - DOIT être en premier
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true; // Bypass ALL permissions
            }
        });
    }
}
