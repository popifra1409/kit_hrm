<?php

namespace App\Policies;

use App\Models\Employee;
use App\Models\User;

class EmployeePolicy
{
    /**
     * Voir la liste des employés
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_employees');
    }

    /**
     * Voir un employé spécifique
     */
    public function view(User $user, Employee $employee): bool
    {
        return $user->can('view_employees');
    }

    /**
     * Créer un employé
     */
    public function create(User $user): bool
    {
        return $user->can('create_employees');
    }

    /**
     * Modifier un employé
     */
    public function update(User $user, Employee $employee): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Supprimer un employé
     */
    public function delete(User $user, Employee $employee): bool
    {
        return $user->can('delete_employees');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, Employee $employee): bool
    {
        return $user->can('delete_employees') && $user->hasRole('super_admin');
    }

    /**
     * Restaurer un employé supprimé
     */
    public function restore(User $user, Employee $employee): bool
    {
        return $user->can('edit_employees');
    }

    /**
     * Voir les employés supprimés
     */
    public function viewTrashed(User $user): bool
    {
        return $user->can('view_employees');
    }

    /**
     * Exporter les employés
     */
    public function export(User $user): bool
    {
        return $user->can('export_employees');
    }
}
