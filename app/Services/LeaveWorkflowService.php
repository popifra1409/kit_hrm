<?php

namespace App\Services;

use App\Models\Leave;
use App\Models\LeaveApproval;
use App\Models\LeaveApprovalStep;
use App\Models\User;

class LeaveWorkflowService
{
    public function submit(Leave $leave): void
    {
        $steps = LeaveApprovalStep::where('is_active', true)->orderBy('order')->get();

        foreach ($steps as $step) {
            LeaveApproval::firstOrCreate(
                ['leave_id' => $leave->id, 'leave_approval_step_id' => $step->id],
                ['step_order' => $step->order, 'status' => 'pending']
            );
        }

        $firstStep = $steps->first();

        $leave->update([
            'status' => 'pending',
            'current_approval_step_id' => $firstStep?->id,
        ]);
    }

    public function approveCurrentStep(Leave $leave, User $user, ?string $comments = null): void
    {
        $approval = $this->getCurrentApproval($leave);

        if (!$approval) {
            return;
        }

        $approval->approve($user->id, $comments);

        $nextApproval = $leave->approvals()
            ->where('step_order', '>', $approval->step_order)
            ->orderBy('step_order')
            ->first();

        if ($nextApproval) {
            $leave->update(['current_approval_step_id' => $nextApproval->leave_approval_step_id]);
        } else {
            $leave->update([
                'status' => 'approved',
                'current_approval_step_id' => null,
                'approved_at_n1' => $leave->approved_at_n1 ?? now(),
            ]);
        }
    }

    public function rejectCurrentStep(Leave $leave, User $user, string $reason): void
    {
        $approval = $this->getCurrentApproval($leave);

        if ($approval) {
            $approval->reject($user->id, $reason);
        }

        $leave->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'rejected_by' => $user->id,
            'rejected_at' => now(),
            'current_approval_step_id' => null,
        ]);
    }

    protected function getCurrentApproval(Leave $leave): ?LeaveApproval
    {
        if (!$leave->current_approval_step_id) {
            return null;
        }

        return $leave->approvals()
            ->where('leave_approval_step_id', $leave->current_approval_step_id)
            ->first();
    }

    public function canUserActOnCurrentStep(Leave $leave, User $user): bool
    {
        $approval = $this->getCurrentApproval($leave);

        if (!$approval || !$approval->isPending()) {
            return false;
        }

        $approvers = $approval->step->resolveApprovers($leave);

        return $approvers->contains('id', $user->id);
    }
}
