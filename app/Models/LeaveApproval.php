<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApproval extends Model
{
    protected $fillable = [
        'leave_id',
        'leave_approval_step_id',
        'step_order',
        'status',
        'resolved_user_id',
        'comments',
        'acted_at',
    ];

    protected $casts = [
        'step_order' => 'integer',
        'acted_at' => 'datetime',
    ];

    public function leave()
    {
        return $this->belongsTo(Leave::class);
    }

    public function step()
    {
        return $this->belongsTo(LeaveApprovalStep::class, 'leave_approval_step_id');
    }

    public function resolvedUser()
    {
        return $this->belongsTo(User::class, 'resolved_user_id');
    }

    public function approve(int $userId, ?string $comments = null): void
    {
        $this->update([
            'status' => 'approved',
            'resolved_user_id' => $userId,
            'comments' => $comments,
            'acted_at' => now(),
        ]);
    }

    public function reject(int $userId, string $comments): void
    {
        $this->update([
            'status' => 'rejected',
            'resolved_user_id' => $userId,
            'comments' => $comments,
            'acted_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
