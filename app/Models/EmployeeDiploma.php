<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeDiploma extends Model
{
    use SoftDeletes;

    public const TYPE_RECRUITMENT = 'recruitment_diploma';
    public const TYPE_HIGHEST = 'highest_diploma';
    public const TYPE_TRAINING = 'training';

    protected $fillable = [
        'employee_id',
        'type',
        'validation_status',
        'title',
        'institution',
        'year_obtained',
        'document_path',
        'is_verified',
        'verified_by',
        'verified_at',
        'rejection_reason',
        'submitted_via',
        'notes',
    ];

    protected $casts = [
        'year_obtained' => 'integer',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function scopeTrainings($query)
    {
        return $query->where('type', self::TYPE_TRAINING);
    }

    public function scopePending($query)
    {
        return $query->where('validation_status', 'pending');
    }

    public function scopeValidated($query)
    {
        return $query->where('validation_status', 'validated');
    }

    public function scopeRejected($query)
    {
        return $query->where('validation_status', 'rejected');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_RECRUITMENT => 'Diplôme de Recrutement',
            self::TYPE_HIGHEST => 'Diplôme le Plus Élevé',
            self::TYPE_TRAINING => 'Formation',
            default => $this->type,
        };
    }

    public function getValidationStatusLabelAttribute(): string
    {
        return match ($this->validation_status) {
            'pending' => 'En attente de validation',
            'validated' => 'Validé',
            'rejected' => 'Rejeté',
            default => $this->validation_status,
        };
    }

    public function isPending(): bool
    {
        return $this->validation_status === 'pending';
    }

    public function isValidated(): bool
    {
        return $this->validation_status === 'validated';
    }

    public function isRejected(): bool
    {
        return $this->validation_status === 'rejected';
    }

    public function validate(int $userId = null): void
    {
        $this->validation_status = 'validated';
        $this->is_verified = true;
        $this->verified_by = $userId ?? auth()->id();
        $this->verified_at = now();
        $this->rejection_reason = null;
        $this->save();
    }

    public function reject(string $reason, int $userId = null): void
    {
        $this->validation_status = 'rejected';
        $this->is_verified = false;
        $this->verified_by = $userId ?? auth()->id();
        $this->verified_at = now();
        $this->rejection_reason = $reason;
        $this->save();
    }

    // Conservée pour compatibilité avec le code existant
    public function markVerified(int $userId = null): void
    {
        $this->validate($userId);
    }
}
