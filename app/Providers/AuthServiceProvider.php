<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     */
    protected $policies = [
        // Utilisateurs & Sécurité
        \App\Models\User::class => \App\Policies\UserPolicy::class,
        
        // Employés & Structure
        \App\Models\Employee::class => \App\Policies\EmployeePolicy::class,
        \App\Models\EmployeeAdvancementHistory::class => \App\Policies\EmployeeAdvancementHistoryPolicy::class,
        \App\Models\EmployeeAssignmentHistory::class => \App\Policies\EmployeeAssignmentHistoryPolicy::class,
        \App\Models\EmployeeAffectation::class => \App\Policies\EmployeeAffectationPolicy::class,
        \App\Models\EmployeeCard::class => \App\Policies\EmployeeCardPolicy::class,
        
        // Structure Organisationnelle
        \App\Models\Department::class => \App\Policies\DepartmentPolicy::class,
        \App\Models\Service::class => \App\Policies\ServicePolicy::class,
        \App\Models\Position::class => \App\Policies\PositionPolicy::class,
        \App\Models\Direction::class => \App\Policies\DirectionPolicy::class,
        \App\Models\SubDirection::class => \App\Policies\SubDirectionPolicy::class,
        \App\Models\Sector::class => \App\Policies\SectorPolicy::class,
        \App\Models\MedicalDepartment::class => \App\Policies\MedicalDepartmentPolicy::class,
        
        // Contrats
        \App\Models\Contract::class => \App\Policies\ContractPolicy::class,
        \App\Models\ContractType::class => \App\Policies\ContractTypePolicy::class,
        
        // Congés & Absences
        \App\Models\Leave::class => \App\Policies\LeavePolicy::class,
        \App\Models\LeaveType::class => \App\Policies\LeaveTypePolicy::class,
        \App\Models\LeaveBalance::class => \App\Policies\LeaveBalancePolicy::class,
        \App\Models\Absence::class => \App\Policies\AbsencePolicy::class,
        \App\Models\Replacement::class => \App\Policies\ReplacementPolicy::class,
        \App\Models\Attendance::class => \App\Policies\AttendancePolicy::class,
        
        // Paie & Rémunération
        \App\Models\SalaryGrid::class => \App\Policies\SalaryGridPolicy::class,
        \App\Models\Payroll::class => \App\Policies\PayrollPolicy::class,
        \App\Models\PayrollItem::class => \App\Policies\PayrollItemPolicy::class,
        
        // Quote-Parts
        \App\Models\RevenueDeclaration::class => \App\Policies\RevenueDeclarationPolicy::class,
        \App\Models\QuotpartPeriod::class => \App\Policies\QuotpartPeriodPolicy::class,
        \App\Models\QuotpartDistribution::class => \App\Policies\QuotpartDistributionPolicy::class,
        \App\Models\QuotpartDeductionType::class => \App\Policies\QuotpartDeductionTypePolicy::class,
        \App\Models\QuotpartParameter::class => \App\Policies\QuotpartParameterPolicy::class,
        \App\Models\MedicalActivity::class => \App\Policies\MedicalActivityPolicy::class,
        
        // Évaluations
        \App\Models\PerformanceEvaluation::class => \App\Policies\PerformanceEvaluationPolicy::class,
        \App\Models\EvaluationCriterion::class => \App\Policies\EvaluationCriterionPolicy::class,
        
        // Formations
        \App\Models\Training::class => \App\Policies\TrainingPolicy::class,
        
        // Documents
        \App\Models\Document::class => \App\Policies\DocumentPolicy::class,
        \App\Models\DocumentCategory::class => \App\Policies\DocumentCategoryPolicy::class,
        
        // Santé
        \App\Models\Dependent::class => \App\Policies\DependentPolicy::class,
        
        // Avancements
        \App\Models\Advancement::class => \App\Policies\AdvancementPolicy::class,
        
        // CNPS & Système
        \App\Models\CnpsPreRegistration::class => \App\Policies\CnpsPreRegistrationPolicy::class,
        \App\Models\Signatory::class => \App\Policies\SignatoryPolicy::class,
        \App\Models\SystemSetting::class => \App\Policies\SystemSettingPolicy::class,
        \App\Models\NotificationTemplate::class => \App\Policies\NotificationTemplatePolicy::class,
    ];

    public function boot(): void
    {
        // ✅ Super Admin bypass - TOUJOURS EN PREMIER
        Gate::before(function ($user, $ability) {
            if ($user->hasRole('super_admin')) {
                return true;
            }
        });
    }
}