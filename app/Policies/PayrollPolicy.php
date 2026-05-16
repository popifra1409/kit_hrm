<?php

namespace App\Policies;

use App\Models\Payroll;
use App\Models\User;

class PayrollPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_payrolls');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, Payroll $payroll): bool
    {
        return $user->can('view_payrolls');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_payrolls');
    }

    /**
     * Modifier
     */
    public function update(User $user, Payroll $payroll): bool
    {
        return $user->can('edit_payrolls');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, Payroll $payroll): bool
    {
        return $user->can('delete_payrolls');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, Payroll $payroll): bool
    {
        return $user->can('edit_payrolls');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, Payroll $payroll): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_payrolls');
    }
}