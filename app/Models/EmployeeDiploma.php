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
        'title',
        'institution',
        'year_obtained',
        'document_path',
        'is_verified',
        'verified_by',
        'verified_at',
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

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_RECRUITMENT => 'Diplôme de Recrutement',
            self::TYPE_HIGHEST => 'Diplôme le Plus Élevé',
            self::TYPE_TRAINING => 'Formation',
            default => $this->type,
        };
    }

    public function markVerified(int $userId = null): void
    {
        $this->is_verified = true;
        $this->verified_by = $userId ?? auth()->id();
        $this->verified_at = now();
        $this->save();
    }
}
