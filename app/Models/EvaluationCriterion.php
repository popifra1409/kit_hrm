<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluationCriterion extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'code',
        'name',
        'description',
        'max_score',
        'weight',
        'applies_to',
        'order',
        'is_active',
    ];

    protected $casts = [
        'max_score' => 'integer',
        'weight' => 'decimal:2',
        'order' => 'integer',
        'is_active' => 'boolean',
    ];

    // ========================================
    // RELATIONS
    // ========================================

    /**
     * Évaluations basées sur ce critère
     */
    public function evaluations()
    {
        return $this->hasMany(EmployeeEvaluation::class, 'criterion_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Critères actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Critères d'une catégorie
     */
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Critères triés par ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    /**
     * Critères applicables à un type de personnel
     */
    public function scopeForPersonnelType($query, $personnelType)
    {
        return $query->where(function ($q) use ($personnelType) {
            $q->where('applies_to', 'all')
                ->orWhere('applies_to', $personnelType);
        });
    }

    // ========================================
    // HELPERS
    // ========================================

    /**
     * Vérifie si le critère s'applique à un type de personnel
     */
    public function appliesTo($personnelType)
    {
        return $this->applies_to === 'all' || $this->applies_to === $personnelType;
    }

    /**
     * Obtenir le nom de la catégorie formaté
     */
    public function getCategoryNameAttribute()
    {
        return match ($this->category) {
            'comportement' => '🎯 Comportement',
            'competence' => '⭐ Compétence',
            'performance' => '🚀 Performance',
            default => $this->category,
        };
    }

    /**
     * Obtenir le label "s'applique à" formaté
     */
    public function getAppliesToLabelAttribute()
    {
        return match ($this->applies_to) {
            'all' => '👥 Tous',
            'soignant' => '👨‍⚕️ Personnel Soignant',
            'non_soignant' => '💼 Personnel Non-Soignant',
            'paramedical' => '🩺 Personnel Paramédical',
            default => $this->applies_to,
        };
    }

    /**
     * Calculer le score pondéré
     */
    public function calculateWeightedScore($score)
    {
        return $score * $this->weight;
    }

    /**
     * Calculer le pourcentage d'un score
     */
    public function calculatePercentage($score)
    {
        if ($this->max_score == 0) {
            return 0;
        }
        return ($score / $this->max_score) * 100;
    }
}
