<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Dependent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'validation_status',
        'validated_by',
        'validated_at',
        'rejection_reason',
        'submitted_via',
        'relationship',
        'first_name',
        'last_name',
        'birth_date',
        'birth_place',
        'gender',
        'phone',
        'email',
        'address',
        'id_card_path',
        'birth_certificate_path',
        'marriage_certificate_path',
        'death_certificate_path',
        'photo_path',
        'is_alive',
        'death_date',
        'is_active',
        'coverage_rate',
        'coverage_start_date',
        'coverage_end_date',
        'card_number',
        'card_issued',
        'card_issue_date',
        'card_expiry_date',
        'card_active',
        'medical_notes',
        'notes',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'death_date' => 'date',
        'validated_at' => 'datetime',
        'coverage_start_date' => 'date',
        'coverage_end_date' => 'date',
        'card_issue_date' => 'date',
        'card_expiry_date' => 'date',
        'is_alive' => 'boolean',
        'is_active' => 'boolean',
        'card_issued' => 'boolean',
        'card_active' => 'boolean',
        'coverage_rate' => 'decimal:2',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Scopes
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

    // Workflow de validation
    public function validate(int $userId = null, ?string $notes = null): void
    {
        $this->validation_status = 'validated';
        $this->validated_by = $userId ?? auth()->id();
        $this->validated_at = now();
        $this->rejection_reason = null;
        if ($notes) {
            $this->notes = trim(($this->notes ? $this->notes . "\n" : '') . $notes);
        }
        $this->save();
    }

    public function reject(string $reason, int $userId = null): void
    {
        $this->validation_status = 'rejected';
        $this->validated_by = $userId ?? auth()->id();
        $this->validated_at = now();
        $this->rejection_reason = $reason;
        $this->save();
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

    public function getValidationStatusLabelAttribute(): string
    {
        return match ($this->validation_status) {
            'pending' => 'En attente de validation',
            'validated' => 'Validé',
            'rejected' => 'Rejeté',
            default => $this->validation_status,
        };
    }

    // Méthodes
    public function getFullNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date->age;
    }

    public function generateCardNumber(): string
    {
        $prefix = match ($this->relationship) {
            'spouse' => 'SP',
            'child' => 'CH',
            'father' => 'FA',
            'mother' => 'MO',
            default => 'AY',
        };

        $number = $prefix . '-' . $this->employee->matricule . '-' . Str::upper(Str::random(4));
        $this->card_number = $number;
        $this->save();

        return $number;
    }

    public function issueCard(): void
    {
        if (!$this->card_number) {
            $this->generateCardNumber();
        }

        $this->card_issued = true;
        $this->card_issue_date = now();
        $this->card_expiry_date = now()->addYear();
        $this->save();
    }

    public function activateCard(): void
    {
        $this->card_active = true;
        $this->is_active = true;
        $this->save();
    }

    public function deactivateCard(): void
    {
        $this->card_active = false;
        $this->save();
    }

    // Libellés
    public function getRelationshipLabel(): string
    {
        return match ($this->relationship) {
            'spouse' => 'Conjoint(e)',
            'child' => 'Enfant',
            'father' => 'Père',
            'mother' => 'Mère',
            default => $this->relationship,
        };
    }
}
