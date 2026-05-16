<?php

namespace App\Policies;

use App\Models\LeaveType;
use App\Models\User;

class LeaveTypePolicy
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
    public function view(User $user, LeaveType $leaveType): bool
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
    public function update(User $user, LeaveType $leaveType): bool
    {
        return $user->can('edit_absences');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, LeaveType $leaveType): bool
    {
        return $user->can('delete_absences');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, LeaveType $leaveType): bool
    {
        return $user->can('edit_absences');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, LeaveType $leaveType): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_absences');
    }
}