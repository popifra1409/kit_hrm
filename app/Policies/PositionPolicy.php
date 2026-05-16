<?php

namespace App\Policies;

use App\Models\Position;
use App\Models\User;

class PositionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('manage_positions') || $user->hasAnyRole(['super_admin', 'admin', 'drh']);
    }

    public function view(User $user, Position $position): bool
    {
        return $user->can('manage_positions') || $user->hasAnyRole(['super_admin', 'admin', 'drh']);
    }

    public function create(User $user): bool
    {
        return $user->can('manage_positions');
    }

    public function update(User $user, Position $position): bool
    {
        return $user->can('manage_positions');
    }

    public function delete(User $user, Position $position): bool
    {
        return $user->can('manage_positions');
    }
}
