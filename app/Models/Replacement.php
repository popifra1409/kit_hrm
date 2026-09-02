<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Replacement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'original_employee_id',
        'replacement_employee_id',
        'start_date',
        'end_date',
        'is_active',
        'reason',
        'temporary_service_id',
        'temporary_qualification_id',
        'responsibilities',
        'has_bonus',
        'bonus_amount',
        'bonus_type',
        'status',
        'approved_by',
        'approved_at',
        'decision_number',
        'decision_date',
        'performance_rating',
        'performance_notes',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'decision_date' => 'date',
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
        'has_bonus' => 'boolean',
        'bonus_amount' => 'decimal:2',
        'performance_rating' => 'integer',
    ];

    // Relations
    public function originalEmployee()
    {
        return $this->belongsTo(Employee::class, 'original_employee_id');
    }

    public function replacementEmployee()
    {
        return $this->belongsTo(Employee::class, 'replacement_employee_id');
    }

    public function temporaryService()
    {
        return $this->belongsTo(Service::class, 'temporary_service_id');
    }

    public function temporaryQualification()
    {
        return $this->belongsTo(Qualification::class, 'temporary_qualification_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Méthodes
    public function approve($approvedBy = null)
    {
        $this->status = 'approved';
        $this->approved_by = $approvedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        return $this;
    }

    public function reject($approvedBy = null)
    {
        $this->status = 'rejected';
        $this->approved_by = $approvedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        return $this;
    }

    public function complete()
    {
        $this->status = 'completed';
        $this->is_active = false;
        $this->save();

        return $this;
    }

    // Vérifier si le remplacement est en cours
    public function isOngoing(): bool
    {
        return $this->is_active
            && $this->status === 'approved'
            && now()->between($this->start_date, $this->end_date);
    }

    // Calculer la durée en jours
    public function getDurationInDays(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    // Libellés
    public function getReasonLabel(): string
    {
        return match ($this->reason) {
            'leave' => 'Congé',
            'sick_leave' => 'Maladie',
            'maternity' => 'Maternité',
            'mission' => 'Mission',
            'training' => 'Formation',
            'other' => 'Autre',
            default => $this->reason,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvé',
            'rejected' => 'Rejeté',
            'completed' => 'Terminé',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            'completed' => 'gray',
            default => 'gray',
        };
    }
}
