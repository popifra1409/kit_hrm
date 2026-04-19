<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'criterion_id',
        'evaluator_id',
        'score',
        'comment',
        'evaluated_at',
    ];

    protected $casts = [
        'score' => 'decimal:2',
        'evaluated_at' => 'datetime',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function period()
    {
        return $this->belongsTo(QuotpartPeriod::class, 'period_id');
    }

    public function criterion()
    {
        return $this->belongsTo(EvaluationCriterion::class, 'criterion_id');
    }

    public function evaluator()
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }

    // Helpers
    public function getWeightedScoreAttribute()
    {
        return $this->score * $this->criterion->weight;
    }

    public function getScorePercentageAttribute()
    {
        return ($this->score / $this->criterion->max_score) * 100;
    }
}
