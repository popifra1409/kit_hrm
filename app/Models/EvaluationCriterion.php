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
        'is_active',
        'order',
    ];

    protected $casts = [
        'max_score' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Relations
    public function evaluations()
    {
        return $this->hasMany(EmployeeEvaluation::class, 'criterion_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // Helpers
    public function appliesTo($personnelType)
    {
        if (!$this->applies_to || $this->applies_to === 'all') {
            return true;
        }
        return $this->applies_to === $personnelType;
    }
}
