<?php

namespace App\Policies;

use App\Models\EmployeeAffectation;
use App\Models\User;

class EmployeeAffectationPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, EmployeeAffectation $employeeAffectation): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Modifier
     */
    public function update(User $user, EmployeeAffectation $employeeAffectation): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, EmployeeAffectation $employeeAffectation): bool
    {
        return $user->can('delete_employees');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, EmployeeAffectation $employeeAffectation): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, EmployeeAffectation $employeeAffectation): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_employees');
    }
}