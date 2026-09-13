<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Permission;

class PermissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_roles');
    }

    public function view(User $user, Permission $permission): bool
    {
        return $user->can('view_roles');
    }

    public function create(User $user): bool
    {
        return $user->hasRole('super_admin');
    }

    public function update(User $user, Permission $permission): bool
    {
        return $user->hasRole('super_admin');
    }

    public function delete(User $user, Permission $permission): bool
    {
        return $user->hasRole('super_admin');
    }

    public function restore(User $user, Permission $permission): bool
    {
        return $user->hasRole('super_admin');
    }

    public function forceDelete(User $user, Permission $permission): bool
    {
        return false;
    }
}
