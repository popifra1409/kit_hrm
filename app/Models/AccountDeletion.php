<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountDeletion extends Model
{
    protected $fillable = [
        'employee_id',
        'matricule',
        'user_name',
        'user_email',
        'reason',
        'notes',
        'initiated_by',
        'deleted_by',
    ];

    public const REASONS = [
        'resignation' => 'Démission',
        'death' => 'Décès',
        'retirement' => 'Retraite',
        'other' => 'Autre',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? $this->reason;
    }
}
