<?php

namespace App\Policies;

use App\Models\SystemSetting;
use App\Models\User;

class SystemSettingPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, SystemSetting $systemSetting): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Modifier
     */
    public function update(User $user, SystemSetting $systemSetting): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, SystemSetting $systemSetting): bool
    {
        return $user->can('delete_settings');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, SystemSetting $systemSetting): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, SystemSetting $systemSetting): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_settings');
    }
}