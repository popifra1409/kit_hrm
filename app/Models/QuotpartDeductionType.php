<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotpartDeductionType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'calculation_type',
        'rate',
        'fixed_amount',
        'progressive_brackets',
        'is_active',
        'order',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'fixed_amount' => 'decimal:2',
        'progressive_brackets' => 'array',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // Calcul de la retenue
    public function calculateDeduction($amount)
    {
        return match ($this->calculation_type) {
            'percentage' => ($amount * $this->rate) / 100,
            'fixed' => $this->fixed_amount,
            'progressive' => $this->calculateProgressiveDeduction($amount),
            default => 0,
        };
    }

    protected function calculateProgressiveDeduction($amount)
    {
        if (!$this->progressive_brackets) {
            return 0;
        }

        $total = 0;
        foreach ($this->progressive_brackets as $bracket) {
            $min = $bracket['min'] ?? 0;
            $max = $bracket['max'] ?? PHP_INT_MAX;
            $rate = $bracket['rate'] ?? 0;

            if ($amount > $min) {
                $taxable = min($amount, $max) - $min;
                $total += ($taxable * $rate) / 100;
            }
        }

        return $total;
    }
}
