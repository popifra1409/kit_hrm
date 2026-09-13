<?php

namespace App\Policies;

use App\Models\LeaveApprovalStep;
use App\Models\User;

class LeaveApprovalStepPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_leave_approval_steps');
    }

    public function view(User $user, LeaveApprovalStep $leaveApprovalStep): bool
    {
        return $user->can('view_leave_approval_steps');
    }

    public function create(User $user): bool
    {
        return $user->can('create_leave_approval_steps');
    }

    public function update(User $user, LeaveApprovalStep $leaveApprovalStep): bool
    {
        return $user->can('edit_leave_approval_steps');
    }

    public function delete(User $user, LeaveApprovalStep $leaveApprovalStep): bool
    {
        return $user->can('delete_leave_approval_steps');
    }

    public function restore(User $user, LeaveApprovalStep $leaveApprovalStep): bool
    {
        return $user->can('edit_leave_approval_steps');
    }

    public function forceDelete(User $user, LeaveApprovalStep $leaveApprovalStep): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_leave_approval_steps');
    }
}
