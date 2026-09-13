<?php

namespace App\Policies;

use App\Models\PublicHoliday;
use App\Models\User;

class PublicHolidayPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_public_holidays');
    }

    public function view(User $user, PublicHoliday $publicHoliday): bool
    {
        return $user->can('view_public_holidays');
    }

    public function create(User $user): bool
    {
        return $user->can('create_public_holidays');
    }

    public function update(User $user, PublicHoliday $publicHoliday): bool
    {
        return $user->can('edit_public_holidays');
    }

    public function delete(User $user, PublicHoliday $publicHoliday): bool
    {
        return $user->can('delete_public_holidays');
    }

    public function restore(User $user, PublicHoliday $publicHoliday): bool
    {
        return $user->can('edit_public_holidays');
    }

    public function forceDelete(User $user, PublicHoliday $publicHoliday): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_public_holidays');
    }
}
