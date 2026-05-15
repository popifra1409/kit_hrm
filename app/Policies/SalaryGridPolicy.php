<?php

namespace App\Policies;

use App\Models\SalaryGrid;
use App\Models\User;

class SalaryGridPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'drh', 'daf']);
    }

    public function view(User $user, SalaryGrid $salaryGrid): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'drh', 'daf']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function update(User $user, SalaryGrid $salaryGrid): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function delete(User $user, SalaryGrid $salaryGrid): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
