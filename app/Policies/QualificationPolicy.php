<?php

namespace App\Policies;

use App\Models\Qualification;
use App\Models\User;

class QualificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_qualifications');
    }

    public function view(User $user, Qualification $qualification): bool
    {
        return $user->can('view_qualifications');
    }

    public function create(User $user): bool
    {
        return $user->can('create_qualifications');
    }

    public function update(User $user, Qualification $qualification): bool
    {
        return $user->can('edit_qualifications');
    }

    public function delete(User $user, Qualification $qualification): bool
    {
        return $user->can('delete_qualifications');
    }

    public function forceDelete(User $user, Qualification $qualification): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_qualifications');
    }

    public function restore(User $user, Qualification $qualification): bool
    {
        return $user->can('edit_qualifications');
    }
}
