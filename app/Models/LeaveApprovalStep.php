<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaveApprovalStep extends Model
{
    protected $fillable = [
        'code',
        'name',
        'order',
        'resolver_type',
        'resolver_role',
        'is_active',
    ];

    protected $casts = [
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function approvals()
    {
        return $this->hasMany(LeaveApproval::class);
    }

    /**
     * Résout la ou les personnes habilitées à approuver cette étape
     * pour une demande de congé donnée.
     *
     * @return \Illuminate\Support\Collection<User>
     */
    public function resolveApprovers(Leave $leave): \Illuminate\Support\Collection
    {
        $employee = $leave->employee;

        return match ($this->resolver_type) {
            'service_head' => collect([$employee?->currentService?->serviceHead?->user])->filter(),

            'department_head' => collect([
                $employee?->currentService?->subDirection?->direction?->director?->user
                    ?? $employee?->department?->departmentHead?->user
            ])->filter(),

            'role' => $this->resolver_role
                ? User::role($this->resolver_role)->get()
                : collect(),

            default => collect(),
        };
    }
}
