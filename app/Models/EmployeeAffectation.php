<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAffectation extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'service_id',
        'position_id',
        'start_date',
        'end_date',
        'reason',
        'decision_number',
        'is_current',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }
}
