<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PerformanceEvaluation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'period_type',
        'evaluation_date',
        'period_start_date',
        'period_end_date',
        'evaluator_id',
        'validator_id',
        'technical_skills',
        'soft_skills',
        'productivity',
        'quality_of_work',
        'initiative',
        'teamwork',
        'punctuality',
        'adaptability',
        'leadership',
        'overall_score',
        'rating',
        'strengths',
        'areas_for_improvement',
        'previous_objectives_review',
        'new_objectives',
        'training_recommendations',
        'evaluator_comments',
        'employee_comments',
        'validator_comments',
        'status',
        'employee_signed_at',
        'evaluator_signed_at',
        'validator_signed_at',
        'evaluation_document',
    ];

    protected $casts = [
        'evaluation_date' => 'date',
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'employee_signed_at' => 'datetime',
        'evaluator_signed_at' => 'datetime',
        'validator_signed_at' => 'datetime',
        'technical_skills' => 'decimal:2',
        'soft_skills' => 'decimal:2',
        'productivity' => 'decimal:2',
        'quality_of_work' => 'decimal:2',
        'initiative' => 'decimal:2',
        'teamwork' => 'decimal:2',
        'punctuality' => 'decimal:2',
        'adaptability' => 'decimal:2',
        'leadership' => 'decimal:2',
        'overall_score' => 'decimal:2',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validator_id');
    }

    // Méthodes de calcul
    public function calculateOverallScore()
    {
        $scores = [
            $this->technical_skills,
            $this->soft_skills,
            $this->productivity,
            $this->quality_of_work,
            $this->initiative,
            $this->teamwork,
            $this->punctuality,
            $this->adaptability,
        ];

        // Ajouter leadership si applicable
        if ($this->leadership) {
            $scores[] = $this->leadership;
        }

        // Filtrer les valeurs nulles
        $scores = array_filter($scores, fn($score) => $score !== null && $score > 0);

        if (count($scores) === 0) {
            $this->overall_score = 0;
            $this->rating = null;
            return $this;
        }

        // Calculer la moyenne
        $this->overall_score = round(array_sum($scores) / count($scores), 2);

        // Déterminer la mention
        $this->rating = match (true) {
            $this->overall_score >= 4.5 => 'excellent',
            $this->overall_score >= 4.0 => 'very_good',
            $this->overall_score >= 3.0 => 'good',
            $this->overall_score >= 2.0 => 'satisfactory',
            default => 'needs_improvement',
        };

        return $this;
    }

    // Signatures
    public function signByEmployee()
    {
        $this->employee_signed_at = now();
        $this->status = 'pending_validator';
        $this->save();

        return $this;
    }

    public function signByEvaluator()
    {
        $this->evaluator_signed_at = now();
        $this->status = 'pending_employee';
        $this->save();

        return $this;
    }

    public function validateByValidator()
    {
        $this->validator_signed_at = now();
        $this->status = 'validated';
        $this->save();

        return $this;
    }

    public function contest()
    {
        $this->status = 'contested';
        $this->save();

        return $this;
    }

    // Libellés
    public function getPeriodTypeLabel(): string
    {
        return match ($this->period_type) {
            'monthly' => 'Mensuelle',
            'quarterly' => 'Trimestrielle',
            'semi_annual' => 'Semestrielle',
            'annual' => 'Annuelle',
            default => $this->period_type,
        };
    }

    public function getRatingLabel(): string
    {
        return match ($this->rating) {
            'excellent' => 'Excellent',
            'very_good' => 'Très Bon',
            'good' => 'Bon',
            'satisfactory' => 'Satisfaisant',
            'needs_improvement' => 'À Améliorer',
            default => 'Non évalué',
        };
    }

    public function getRatingColor(): string
    {
        return match ($this->rating) {
            'excellent' => 'success',
            'very_good' => 'info',
            'good' => 'primary',
            'satisfactory' => 'warning',
            'needs_improvement' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Brouillon',
            'pending_employee' => 'En attente employé',
            'pending_validator' => 'En attente validation',
            'validated' => 'Validée',
            'contested' => 'Contestée',
            default => $this->status,
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            // Calculer automatiquement le score global
            $evaluation->calculateOverallScore();
        });
    }
}
