<?php

namespace App\Policies;

use App\Models\Signatory;
use App\Models\User;

class SignatoryPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_settings');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, Signatory $signatory): bool
    {
        return $user->can('view_settings');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_settings');
    }

    /**
     * Modifier
     */
    public function update(User $user, Signatory $signatory): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, Signatory $signatory): bool
    {
        return $user->can('delete_settings');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, Signatory $signatory): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, Signatory $signatory): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_settings');
    }
}