<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryGrid extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'echelon',
        'base_salary',
        'effective_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'category' => 'integer',
        'echelon' => 'integer',
        'base_salary' => 'decimal:2',
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    // Méthode statique pour obtenir le salaire de base
    public static function getBaseSalary($category, $echelon, $date = null)
    {
        $date = $date ?? now();

        return self::where('category', $category)
            ->where('echelon', $echelon)
            ->where('effective_date', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->where('is_active', true)
            ->value('base_salary') ?? 0;
    }
}
