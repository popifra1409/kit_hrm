<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PayrollItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'category',
        'is_taxable',
        'is_subject_to_cnps',
        'calculation_method',
        'fixed_amount',
        'percentage',
        'formula',
        'display_order',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_taxable' => 'boolean',
        'is_subject_to_cnps' => 'boolean',
        'is_active' => 'boolean',
        'fixed_amount' => 'decimal:2',
        'percentage' => 'decimal:2',
        'display_order' => 'integer',
    ];

    public function payrollLines()
    {
        return $this->hasMany(PayrollLine::class);
    }

    // Calculer le montant pour un employé
    public function calculateAmount($baseSalary, $taxableSalary = null, $cnpsSalary = null)
    {
        switch ($this->calculation_method) {
            case 'fixed':
                return $this->fixed_amount ?? 0;

            case 'percentage':
                // Déterminer la base de calcul
                if ($this->is_subject_to_cnps && $cnpsSalary) {
                    return ($cnpsSalary * $this->percentage) / 100;
                } elseif ($this->is_taxable && $taxableSalary) {
                    return ($taxableSalary * $this->percentage) / 100;
                } else {
                    return ($baseSalary * $this->percentage) / 100;
                }

            case 'formula':
                // Pour les formules complexes (IRPP par exemple)
                return 0; // Sera calculé spécifiquement

            default:
                return 0;
        }
    }
}
