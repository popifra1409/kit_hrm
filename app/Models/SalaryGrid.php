<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SalaryGrid extends Model
{
    protected $fillable = [
        'classification_type',
        'category',
        'echelon',
        'base_salary',
        'effective_date',
        'end_date',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        // ❌ N'PAS caster category et echelon en integer
        // ✅ Les laisser comme string/varchar
    ];

    /**
     * Récupérer le salaire de base
     */
    public static function getBaseSalary($category, $echelon): float
    {
        $salary = self::where('category', (string) $category)
            ->where('echelon', (string) $echelon)
            ->where('is_active', true)
            ->latest('effective_date')
            ->first();

        if (!$salary) {
            throw new \Exception("Grille salariale non trouvée pour {$category}/{$echelon}");
        }

        return (float) $salary->base_salary;
    }
}
