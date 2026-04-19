<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotpartPeriod extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'year',
        'month',
        'start_date',
        'end_date',
        'total_revenue',
        'quotpart_percentage',
        'quotpart_amount',
        'status',
        'calculated_at',
        'validated_at',
        'validated_by',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_revenue' => 'decimal:2',
        'quotpart_percentage' => 'decimal:2',
        'quotpart_amount' => 'decimal:2',
        'calculated_at' => 'datetime',
        'validated_at' => 'datetime',
    ];

    // Relations
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function distributions()
    {
        return $this->hasMany(QuotpartDistribution::class, 'period_id');
    }

    public function evaluations()
    {
        return $this->hasMany(EmployeeEvaluation::class, 'period_id');
    }

    public function medicalActivities()
    {
        return $this->hasMany(MedicalActivity::class, 'period_id');
    }

    public function revenueDeclarations()
    {
        return $this->hasMany(RevenueDeclaration::class, 'period_id');
    }

    // Scopes
    public function scopeCurrent($query)
    {
        return $query->where('year', now()->year)
            ->where('month', now()->month);
    }

    public function scopeValidated($query)
    {
        return $query->where('status', 'validated');
    }

    // Helpers
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

    public function getFullNameAttribute()
    {
        return $this->month_name . ' ' . $this->year;
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function isCalculated()
    {
        return in_array($this->status, ['calculated', 'distributed']);
    }

    public function canCalculate()
    {
        return $this->status === 'validated' && $this->total_revenue > 0;
    }

    // Calculer le montant à distribuer
    public function calculateQuotpartAmount()
    {
        $this->quotpart_amount = ($this->total_revenue * $this->quotpart_percentage) / 100;
        $this->save();
        return $this->quotpart_amount;
    }
}
