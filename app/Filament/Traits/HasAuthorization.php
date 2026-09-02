<?php

namespace App\Filament\Traits;

trait HasAuthorization
{
    /**
     * Désactiver les actions selon les permissions
     */
    public static function canViewAny(): bool
    {
        return static::checkCan('viewAny');
    }

    public static function canCreate(): bool
    {
        return static::checkCan('create');
    }

    public static function canEdit($record): bool
    {
        return static::checkCan('update', $record);
    }

    public static function canDelete($record): bool
    {
        return static::checkCan('delete', $record);
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->can('delete_' . static::getPluralModelName()) ?? false;
    }

    /**
     * Vérifier une permission via Policy
     * ✅ PUBLIC au lieu de PROTECTED
     */
    public static function checkCan(string $ability, $record = null): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        $model = static::getModel();

        if ($record) {
            return $user->can($ability, $record);
        }

        return $user->can($ability, $model);
    }

    /**
     * Obtenir le nom du modèle au pluriel
     */
    protected static function getPluralModelName(): string
    {
        $modelClass = static::getModel();
        $modelName = class_basename($modelClass);

        $plurals = [
            // Employés
            'Employee' => 'employees',
            'EmployeeAdvancementHistory' => 'employees',
            'EmployeeAssignmentHistory' => 'employees',
            'EmployeeAffectation' => 'employees',
            'EmployeeCard' => 'employee_cards',

            // Structure
            'Department' => 'departments',
            'Service' => 'services',
            'Direction' => 'departments',
            'SubDirection' => 'services',
            'Sector' => 'services',
            'MedicalDepartment' => 'departments',
            'TradeBody' => 'trade_bodies',
            'Qualification' => 'qualifications',
            'JobTitle' => 'job_titles',

            // Contrats
            'Contract' => 'contracts',
            'ContractType' => 'contract_types',

            // Congés
            'Leave' => 'leaves',
            'LeaveType' => 'leaves',
            'LeaveBalance' => 'leaves',
            'Absence' => 'absences',
            'Replacement' => 'absences',
            'Attendance' => 'attendances',

            // Paie
            'SalaryGrid' => 'salary_grids',
            'Payroll' => 'payrolls',
            'PayrollItem' => 'payrolls',

            // Quote-Parts
            'RevenueDeclaration' => 'quotparts',
            'QuotpartPeriod' => 'quotparts',
            'QuotpartDistribution' => 'quotparts',
            'QuotpartDeductionType' => 'quotparts',
            'QuotpartParameter' => 'quotparts',
            'MedicalActivity' => 'medical_activities',

            // Évaluations
            'PerformanceEvaluation' => 'evaluations',
            'EvaluationCriterion' => 'evaluations',

            // Autres
            'Training' => 'trainings',
            'Document' => 'documents',
            'DocumentCategory' => 'documents',
            'Dependent' => 'dependents',
            'Advancement' => 'employees',
            'CnpsPreRegistration' => 'employees',
            'Signatory' => 'settings',
            'SystemSetting' => 'settings',
            'NotificationTemplate' => 'settings',
            'User' => 'users',
        ];

        return $plurals[$modelName] ?? strtolower($modelName) . 's';
    }
}
