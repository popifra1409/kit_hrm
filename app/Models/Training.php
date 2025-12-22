<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Training extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'code',
        'description',
        'type',
        'category',
        'trainer_name',
        'training_organization',
        'trainer_bio',
        'start_date',
        'end_date',
        'duration_hours',
        'duration_days',
        'location',
        'room',
        'is_online',
        'online_link',
        'max_participants',
        'min_participants',
        'cost_per_participant',
        'total_budget',
        'budget_source',
        'objectives',
        'prerequisites',
        'program',
        'materials_needed',
        'materials_provided',
        'has_evaluation',
        'has_certificate',
        'certificate_template',
        'status',
        'coordinator_id',
        'syllabus_document',
        'report_document',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_online' => 'boolean',
        'has_evaluation' => 'boolean',
        'has_certificate' => 'boolean',
        'cost_per_participant' => 'decimal:2',
        'total_budget' => 'decimal:2',
    ];

    // Relations
    public function coordinator()
    {
        return $this->belongsTo(User::class, 'coordinator_id');
    }

    public function participants()
    {
        return $this->hasMany(TrainingParticipant::class);
    }

    public function approvedParticipants()
    {
        return $this->participants()->where('registration_status', 'approved');
    }

    public function presentParticipants()
    {
        return $this->participants()->where('attendance_status', 'present');
    }

    // Méthodes
    public function getParticipantsCount(): int
    {
        return $this->approvedParticipants()->count();
    }

    public function getAttendanceRate(): float
    {
        $total = $this->approvedParticipants()->count();

        if ($total === 0) {
            return 0;
        }

        $present = $this->presentParticipants()->count();

        return round(($present / $total) * 100, 2);
    }

    public function getAverageSatisfaction(): ?float
    {
        $ratings = $this->participants()
            ->whereNotNull('satisfaction_rating')
            ->pluck('satisfaction_rating');

        if ($ratings->isEmpty()) {
            return null;
        }

        return round($ratings->average(), 2);
    }

    public function canRegister(): bool
    {
        return $this->status === 'registration_open'
            && ($this->max_participants === null || $this->getParticipantsCount() < $this->max_participants);
    }

    // Libellés
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'internal' => 'Formation Interne',
            'external' => 'Formation Externe',
            'online' => 'Formation en Ligne',
            'workshop' => 'Atelier',
            'seminar' => 'Séminaire',
            'certification' => 'Certification',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'planned' => 'Planifiée',
            'registration_open' => 'Inscriptions Ouvertes',
            'registration_closed' => 'Inscriptions Fermées',
            'in_progress' => 'En Cours',
            'completed' => 'Terminée',
            'cancelled' => 'Annulée',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'planned' => 'gray',
            'registration_open' => 'success',
            'registration_closed' => 'warning',
            'in_progress' => 'info',
            'completed' => 'primary',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }
}
