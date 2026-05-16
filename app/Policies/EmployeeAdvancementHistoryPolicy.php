<?php

namespace App\Policies;

use App\Models\EmployeeAdvancementHistory;
use App\Models\User;

class EmployeeAdvancementHistoryPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_employees');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, EmployeeAdvancementHistory $employeeAdvancementHistory): bool
    {
        return $user->can('view_employees');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_employees');
    }

    /**
     * Modifier
     */
    public function update(User $user, EmployeeAdvancementHistory $employeeAdvancementHistory): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, EmployeeAdvancementHistory $employeeAdvancementHistory): bool
    {
        return $user->can('delete_employees');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, EmployeeAdvancementHistory $employeeAdvancementHistory): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, EmployeeAdvancementHistory $employeeAdvancementHistory): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_employees');
    }
}