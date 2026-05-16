<?php

namespace App\Policies;

use App\Models\EmployeeCard;
use App\Models\User;

class EmployeeCardPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_employee_cards');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, EmployeeCard $employeeCard): bool
    {
        return $user->can('view_employee_cards');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_employee_cards');
    }

    /**
     * Modifier
     */
    public function update(User $user, EmployeeCard $employeeCard): bool
    {
        return $user->can('edit_employee_cards');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, EmployeeCard $employeeCard): bool
    {
        return $user->can('delete_employee_cards');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, EmployeeCard $employeeCard): bool
    {
        return $user->can('edit_employee_cards');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, EmployeeCard $employeeCard): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_employee_cards');
    }
}