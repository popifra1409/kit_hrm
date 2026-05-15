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

        // Conversion simple au pluriel
        $plurals = [
            'Employee' => 'employees',
            'Dependent' => 'dependents',
            'Contract' => 'contracts',
            'ContractType' => 'contract_types',
            'SalaryGrid' => 'salary_grids',
            'Leave' => 'leaves',
        ];

        return $plurals[$modelName] ?? strtolower($modelName) . 's';
    }
}
