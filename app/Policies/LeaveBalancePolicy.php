<?php

namespace App\Policies;

use App\Models\LeaveBalance;
use App\Models\User;

class LeaveBalancePolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_leaves');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('view_leaves');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_leaves');
    }

    /**
     * Modifier
     */
    public function update(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('edit_leaves');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('delete_leaves');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->can('edit_leaves');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, LeaveBalance $leaveBalance): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_leaves');
    }
}