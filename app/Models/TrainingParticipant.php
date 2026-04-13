<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'training_id',
        'employee_id',
        'registration_status',
        'registered_at',
        'approved_by',
        'approved_at',
        'attendance_status',
        'hours_attended',
        'absence_reason',
        'satisfaction_rating',
        'content_rating',
        'trainer_rating',
        'usefulness_rating',
        'feedback',
        'suggestions',
        'participation_score',
        'test_score',
        'passed',
        'trainer_comments',
        'certificate_issued',
        'certificate_number',
        'certificate_date',
        'certificate_file',
        'skills_acquired',
        'application_plan',
        'follow_up_date',
        'follow_up_notes',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'approved_at' => 'datetime',
        'certificate_date' => 'date',
        'follow_up_date' => 'date',
        'certificate_issued' => 'boolean',
        'passed' => 'boolean',
    ];

    // Relations
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Méthodes
    public function approve($approvedBy = null)
    {
        $this->registration_status = 'approved';
        $this->approved_by = $approvedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        return $this;
    }

    public function reject()
    {
        $this->registration_status = 'rejected';
        $this->save();

        return $this;
    }

    public function markPresent()
    {
        $this->attendance_status = 'present';
        $this->save();

        return $this;
    }

    public function markAbsent($reason = null)
    {
        $this->attendance_status = 'absent';
        $this->absence_reason = $reason;
        $this->save();

        return $this;
    }

    public function calculateAverageRating(): ?float
    {
        $ratings = array_filter([
            $this->satisfaction_rating,
            $this->content_rating,
            $this->trainer_rating,
            $this->usefulness_rating,
        ]);

        if (empty($ratings)) {
            return null;
        }

        return round(array_sum($ratings) / count($ratings), 2);
    }

    // Libellés
    public function getRegistrationStatusLabel(): string
    {
        return match ($this->registration_status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            'waitlist' => 'Liste d\'attente',
            default => $this->registration_status,
        };
    }

    public function getAttendanceStatusLabel(): string
    {
        return match ($this->attendance_status) {
            'registered' => 'Inscrit',
            'present' => 'Présent',
            'absent' => 'Absent',
            'partial' => 'Présence Partielle',
            default => $this->attendance_status,
        };
    }
}
