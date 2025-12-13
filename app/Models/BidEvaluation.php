<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BidEvaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'bid_id',
        'evaluator_id',
        'technical_score',
        'financial_score',
        'experience_score',
        'capacity_score',
        'methodology_score',
        'total_score',
        'recommendation',
        'strengths',
        'weaknesses',
        'comments',
        'evaluated_at',
    ];

    protected $casts = [
        'technical_score' => 'decimal:2',
        'financial_score' => 'decimal:2',
        'experience_score' => 'decimal:2',
        'capacity_score' => 'decimal:2',
        'methodology_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'evaluated_at' => 'datetime',
    ];

    public function bid()
    {
        return $this->belongsTo(Bid::class);
    }

    public function evaluator()
    {
        return $this->belongsTo(User::class, 'evaluator_id');
    }

    // Calculer automatiquement le score total
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($evaluation) {
            $evaluation->total_score =
                ($evaluation->technical_score ?? 0) +
                ($evaluation->financial_score ?? 0) +
                ($evaluation->experience_score ?? 0) +
                ($evaluation->capacity_score ?? 0) +
                ($evaluation->methodology_score ?? 0);
        });
    }
}
