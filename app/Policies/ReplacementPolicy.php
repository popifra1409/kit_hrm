<?php

namespace App\Policies;

use App\Models\Replacement;
use App\Models\User;

class ReplacementPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_absences');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, Replacement $replacement): bool
    {
        return $user->can('view_absences');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_absences');
    }

    /**
     * Modifier
     */
    public function update(User $user, Replacement $replacement): bool
    {
        return $user->can('edit_absences');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, Replacement $replacement): bool
    {
        return $user->can('delete_absences');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, Replacement $replacement): bool
    {
        return $user->can('edit_absences');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, Replacement $replacement): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_absences');
    }
}