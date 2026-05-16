<?php

namespace App\Policies;

use App\Models\Training;
use App\Models\User;

class TrainingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_trainings');
    }

    public function view(User $user, Training $training): bool
    {
        return $user->can('view_trainings');
    }

    public function create(User $user): bool
    {
        return $user->can('create_trainings');
    }

    public function update(User $user, Training $training): bool
    {
        return $user->can('edit_trainings');
    }

    public function delete(User $user, Training $training): bool
    {
        return $user->can('delete_trainings');
    }

    public function manageParticipants(User $user, Training $training): bool
    {
        return $user->can('manage_training_participants');
    }
}
