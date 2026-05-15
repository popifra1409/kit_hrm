<?php

namespace App\Policies;

use App\Models\Dependent;
use App\Models\User;

class DependentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_dependents');
    }

    public function view(User $user, Dependent $dependent): bool
    {
        return $user->can('view_dependents');
    }

    public function create(User $user): bool
    {
        return $user->can('create_dependents');
    }

    public function update(User $user, Dependent $dependent): bool
    {
        return $user->can('edit_dependents');
    }

    public function delete(User $user, Dependent $dependent): bool
    {
        return $user->can('delete_dependents');
    }

    public function issueCard(User $user, Dependent $dependent): bool
    {
        return $user->can('issue_health_cards');
    }

    public function activateCard(User $user, Dependent $dependent): bool
    {
        return $user->can('activate_health_cards');
    }

    public function deactivateCard(User $user, Dependent $dependent): bool
    {
        return $user->can('activate_health_cards');
    }
}
