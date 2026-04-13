<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'month',
        'year',
        'payment_date',
        'base_salary',
        'total_gains',
        'total_deductions',
        'gross_taxable',
        'gross_cnps',
        'gross_salary',
        'net_salary',
        'cnps_employee',
        'cnps_employer',
        'irpp',
        'cac',
        'status',
        'validated_by',
        'validated_at',
        'notes',
    ];

    protected $casts = [
        'month' => 'integer',
        'year' => 'integer',
        'payment_date' => 'date',
        'base_salary' => 'decimal:2',
        'total_gains' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'gross_taxable' => 'decimal:2',
        'gross_cnps' => 'decimal:2',
        'gross_salary' => 'decimal:2',
        'net_salary' => 'decimal:2',
        'cnps_employee' => 'decimal:2',
        'cnps_employer' => 'decimal:2',
        'irpp' => 'decimal:2',
        'cac' => 'decimal:2',
        'validated_at' => 'datetime',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function lines()
    {
        return $this->hasMany(PayrollLine::class);
    }

    // Accessor pour le mois en français
    public function getMonthNameAttribute()
    {
        $months = [
            1 => 'Janvier',
            2 => 'Février',
            3 => 'Mars',
            4 => 'Avril',
            5 => 'Mai',
            6 => 'Juin',
            7 => 'Juillet',
            8 => 'Août',
            9 => 'Septembre',
            10 => 'Octobre',
            11 => 'Novembre',
            12 => 'Décembre'
        ];
        return $months[$this->month] ?? '';
    }

    // Calculer l'IRPP selon le barème camerounais
    public static function calculateIRPP($taxableSalary)
    {
        // Barème IRPP Cameroun 2025
        if ($taxableSalary <= 62000) {
            return 0;
        } elseif ($taxableSalary <= 100000) {
            return ($taxableSalary - 62000) * 0.10;
        } elseif ($taxableSalary <= 200000) {
            return 3800 + (($taxableSalary - 100000) * 0.15);
        } elseif ($taxableSalary <= 300000) {
            return 18800 + (($taxableSalary - 200000) * 0.20);
        } elseif ($taxableSalary <= 500000) {
            return 38800 + (($taxableSalary - 300000) * 0.25);
        } else {
            return 88800 + (($taxableSalary - 500000) * 0.35);
        }
    }

    // Recalculer tous les totaux
    public function recalculate()
    {
        // Total gains (imposables et non imposables)
        $this->total_gains = $this->lines()
            ->where('type', 'gain')
            ->sum('amount');

        // Total retenues
        $this->total_deductions = $this->lines()
            ->where('type', 'deduction')
            ->sum('amount');

        // Salaire imposable (gains imposables uniquement)
        $this->gross_taxable = $this->lines()
            ->where('type', 'gain')
            ->where('is_taxable', true)
            ->sum('amount');

        // Salaire cotisable CNPS
        $this->gross_cnps = $this->lines()
            ->where('type', 'gain')
            ->where('is_subject_to_cnps', true)
            ->sum('amount');

        // Salaire brut total
        $this->gross_salary = $this->total_gains;

        // Net à payer
        $this->net_salary = $this->gross_salary - $this->total_deductions;

        // CNPS employé et employeur
        $this->cnps_employee = $this->lines()
            ->where('type', 'deduction')
            ->whereHas('payrollItem', function ($query) {
                $query->where('code', 'PENSION');
            })
            ->sum('amount');

        $this->cnps_employer = ($this->gross_cnps * 11.2) / 100; // Part employeur 11.2%

        // IRPP et CAC
        $this->irpp = $this->lines()
            ->where('type', 'deduction')
            ->whereHas('payrollItem', function ($query) {
                $query->where('code', 'IRPP');
            })
            ->sum('amount');

        $this->cac = $this->lines()
            ->where('type', 'deduction')
            ->whereHas('payrollItem', function ($query) {
                $query->where('code', 'CAC');
            })
            ->sum('amount');

        $this->save();
    }
}
