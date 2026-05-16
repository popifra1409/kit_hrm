<?php

namespace App\Policies;

use App\Models\PayrollItem;
use App\Models\User;

class PayrollItemPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('edit_payrolls');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, PayrollItem $payrollItem): bool
    {
        return $user->can('edit_payrolls');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('edit_payrolls');
    }

    /**
     * Modifier
     */
    public function update(User $user, PayrollItem $payrollItem): bool
    {
        return $user->can('edit_payrolls');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, PayrollItem $payrollItem): bool
    {
        return $user->can('delete_payrolls');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, PayrollItem $payrollItem): bool
    {
        return $user->can('edit_payrolls');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, PayrollItem $payrollItem): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_payrolls');
    }
}