<?php

namespace App\Policies;

use App\Models\NotificationTemplate;
use App\Models\User;

class NotificationTemplatePolicy
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
    public function view(User $user, NotificationTemplate $notificationTemplate): bool
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
    public function update(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('delete_settings');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->can('edit_settings');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, NotificationTemplate $notificationTemplate): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_settings');
    }
}