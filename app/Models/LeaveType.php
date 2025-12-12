<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaveType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'default_days',
        'max_days_per_year',
        'requires_document',
        'is_paid',
        'deductible_from_annual',
        'is_active',
    ];

    protected $casts = [
        'default_days' => 'integer',
        'max_days_per_year' => 'integer',
        'requires_document' => 'boolean',
        'is_paid' => 'boolean',
        'deductible_from_annual' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function balances()
    {
        return $this->hasMany(LeaveBalance::class);
    }
}
