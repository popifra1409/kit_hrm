<?php

namespace App\Policies;

use App\Models\LeaveDecision;
use App\Models\User;

class LeaveDecisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_leave_decisions');
    }

    public function view(User $user, LeaveDecision $leaveDecision): bool
    {
        return $user->can('view_leave_decisions');
    }

    public function create(User $user): bool
    {
        return $user->can('create_leave_decisions');
    }

    public function update(User $user, LeaveDecision $leaveDecision): bool
    {
        return $user->can('edit_leave_decisions');
    }

    public function delete(User $user, LeaveDecision $leaveDecision): bool
    {
        return $user->can('delete_leave_decisions');
    }

    public function restore(User $user, LeaveDecision $leaveDecision): bool
    {
        return $user->can('edit_leave_decisions');
    }

    public function forceDelete(User $user, LeaveDecision $leaveDecision): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_leave_decisions');
    }
}
