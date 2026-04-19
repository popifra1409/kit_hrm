<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'period_id',
        'activity_type',
        'quantity',
        'unit_value',
        'total_value',
        'details',
        'activity_date',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_value' => 'decimal:2',
        'total_value' => 'decimal:2',
        'activity_date' => 'date',
        'validated_at' => 'datetime',
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

    public function validator()
    {
        return $this->belongsTo(Employee::class, 'validated_by');
    }

    // Scopes
    public function scopeValidated($query)
    {
        return $query->whereNotNull('validated_at');
    }

    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    // Helpers
    public function calculateTotalValue()
    {
        if ($this->unit_value) {
            $this->total_value = $this->quantity * $this->unit_value;
            $this->save();
        }
        return $this->total_value;
    }

    public function getActivityTypeNameAttribute()
    {
        return match ($this->activity_type) {
            'consultation' => 'Consultation',
            'prescription' => 'Prescription',
            'acte' => 'Acte médical',
            'garde' => 'Garde',
            'astreinte' => 'Astreinte',
            default => $this->activity_type,
        };
    }
}
