<?php

namespace App\Policies;

use App\Models\Leave;
use App\Models\User;

class LeavePolicy
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
    public function view(User $user, Leave $leave): bool
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
    public function update(User $user, Leave $leave): bool
    {
        return $user->can('edit_leaves');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, Leave $leave): bool
    {
        return $user->can('delete_leaves');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, Leave $leave): bool
    {
        return $user->can('edit_leaves');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, Leave $leave): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_leaves');
    }

    /**
     * Approuver les congés
     */
    public function approve(User $user): bool
    {
        return $user->can('approve_leaves');
    }

    /**
     * Rejeter les congés
     */
    public function reject(User $user): bool
    {
        return $user->can('reject_leaves');
    }
}
