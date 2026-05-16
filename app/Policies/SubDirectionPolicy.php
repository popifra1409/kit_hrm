<?php

namespace App\Policies;

use App\Models\SubDirection;
use App\Models\User;

class SubDirectionPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_services');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, SubDirection $subDirection): bool
    {
        return $user->can('view_services');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_services');
    }

    /**
     * Modifier
     */
    public function update(User $user, SubDirection $subDirection): bool
    {
        return $user->can('edit_services');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, SubDirection $subDirection): bool
    {
        return $user->can('delete_services');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, SubDirection $subDirection): bool
    {
        return $user->can('edit_services');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, SubDirection $subDirection): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_services');
    }
}