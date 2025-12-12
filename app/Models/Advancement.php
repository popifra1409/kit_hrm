<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Advancement extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'previous_category',
        'new_category',
        'previous_echelon',
        'new_echelon',
        'advancement_date',
        'type',
        'reason',
        'decision_number',
        'status',
        'notes',
    ];

    protected $casts = [
        'advancement_date' => 'date',
        'previous_echelon' => 'integer',
        'new_echelon' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}
