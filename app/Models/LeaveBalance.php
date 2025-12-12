<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'total_entitled',
        'used',
        'pending',
        'available',
    ];

    protected $casts = [
        'year' => 'integer',
        'total_entitled' => 'integer',
        'used' => 'integer',
        'pending' => 'integer',
        'available' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    // Recalculer le solde disponible
    public function recalculate()
    {
        $this->available = $this->total_entitled - $this->used - $this->pending;
        $this->save();
    }
}
