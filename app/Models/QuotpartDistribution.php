<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotpartDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'base_indice_points',
        'evaluation_points',
        'medical_activity_points',
        'management_bonus_points',
        'anciennete_points',
        'total_points',
        'gross_quotpart',
        'cnps_deduction',
        'irpp_deduction',
        'other_deductions',
        'total_deductions',
        'net_quotpart',
        'calculation_details',
        'notes',
        'status',
        'calculated_at',
        'paid_at',
    ];

    protected $casts = [
        'base_indice_points' => 'decimal:2',
        'evaluation_points' => 'decimal:2',
        'medical_activity_points' => 'decimal:2',
        'management_bonus_points' => 'decimal:2',
        'anciennete_points' => 'decimal:2',
        'total_points' => 'decimal:2',
        'gross_quotpart' => 'decimal:2',
        'cnps_deduction' => 'decimal:2',
        'irpp_deduction' => 'decimal:2',
        'other_deductions' => 'decimal:2',
        'total_deductions' => 'decimal:2',
        'net_quotpart' => 'decimal:2',
        'calculation_details' => 'array',
        'calculated_at' => 'datetime',
        'paid_at' => 'datetime',
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

    public function payments()
    {
        return $this->hasMany(QuotpartPaymentHistory::class, 'distribution_id');
    }

    // Scopes
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['calculated', 'validated']);
    }

    // Helpers
    public function calculateTotalPoints()
    {
        $this->total_points =
            $this->base_indice_points +
            $this->evaluation_points +
            $this->medical_activity_points +
            $this->management_bonus_points +
            $this->anciennete_points;

        return $this->total_points;
    }

    public function calculateDeductions()
    {
        $this->total_deductions =
            $this->cnps_deduction +
            $this->irpp_deduction +
            $this->other_deductions;

        $this->net_quotpart = $this->gross_quotpart - $this->total_deductions;

        return $this->total_deductions;
    }

    public function isPaid()
    {
        return $this->status === 'paid';
    }

    public function canBePaid()
    {
        return in_array($this->status, ['calculated', 'validated']) && $this->net_quotpart > 0;
    }
}
