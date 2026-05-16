<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Voir la liste des utilisateurs
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_users');
    }

    /**
     * Voir un utilisateur spécifique
     */
    public function view(User $user, User $model): bool
    {
        return $user->can('view_users');
    }

    /**
     * Créer un utilisateur
     */
    public function create(User $user): bool
    {
        return $user->can('create_users');
    }

    /**
     * Modifier un utilisateur
     */
    public function update(User $user, User $model): bool
    {
        // Un utilisateur peut toujours modifier son propre profil
        if ($user->id === $model->id) {
            return true;
        }

        return $user->can('edit_users');
    }

    /**
     * Supprimer un utilisateur
     */
    public function delete(User $user, User $model): bool
    {
        // Ne peut pas se supprimer soi-même
        if ($user->id === $model->id) {
            return false;
        }

        // Ne peut pas supprimer un super admin
        if ($model->hasRole('super_admin')) {
            return false;
        }

        return $user->can('delete_users');
    }

    /**
     * Restaurer un utilisateur supprimé
     */
    public function restore(User $user, User $model): bool
    {
        return $user->can('edit_users');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_users');
    }

    /**
     * Gérer les rôles
     */
    public function manageRoles(User $user): bool
    {
        return $user->can('manage_roles');
    }

    /**
     * Gérer les permissions
     */
    public function managePermissions(User $user): bool
    {
        return $user->can('manage_permissions');
    }
}
