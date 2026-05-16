<?php

namespace App\Policies;

use App\Models\Direction;
use App\Models\User;

class DirectionPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_departments');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, Direction $direction): bool
    {
        return $user->can('view_departments');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_departments');
    }

    /**
     * Modifier
     */
    public function update(User $user, Direction $direction): bool
    {
        return $user->can('edit_departments');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, Direction $direction): bool
    {
        return $user->can('delete_departments');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, Direction $direction): bool
    {
        return $user->can('edit_departments');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, Direction $direction): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_departments');
    }
}