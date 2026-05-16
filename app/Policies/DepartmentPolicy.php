<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_departments') || $user->hasAnyRole(['super_admin', 'admin', 'drh']);
    }

    public function view(User $user, Department $department): bool
    {
        return $user->can('manage_departments') || $user->hasAnyRole(['super_admin', 'admin', 'drh']);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_departments');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->can('manage_departments');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->can('manage_departments');
    }
}
