<?php

namespace App\Policies;

use App\Models\Advancement;
use App\Models\User;

class AdvancementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_employees');
    }

    public function view(User $user, Advancement $advancement): bool
    {
        return $user->can('view_employees');
    }

    public function create(User $user): bool
    {
        return $user->can('edit_employees');
    }

    public function update(User $user, Advancement $advancement): bool
    {
        return $user->can('edit_employees');
    }

    public function delete(User $user, Advancement $advancement): bool
    {
        return $user->can('delete_employees');
    }
}
