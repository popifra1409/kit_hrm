<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_attendances');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, Attendance $attendance): bool
    {
        return $user->can('view_attendances');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_attendances');
    }

    /**
     * Modifier
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->can('edit_attendances');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, Attendance $attendance): bool
    {
        return $user->can('delete_attendances');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, Attendance $attendance): bool
    {
        return $user->can('edit_attendances');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_attendances');
    }
}