<?php

namespace App\Policies;

use App\Models\Absence;
use App\Models\User;

class AbsencePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_absences');
    }

    public function view(User $user, Absence $absence): bool
    {
        return $user->can('view_absences');
    }

    public function create(User $user): bool
    {
        return $user->can('manage_absences');
    }

    public function update(User $user, Absence $absence): bool
    {
        return $user->can('manage_absences');
    }

    public function delete(User $user, Absence $absence): bool
    {
        return $user->can('manage_absences');
    }

    public function justify(User $user, Absence $absence): bool
    {
        return $user->can('manage_absences');
    }
}
