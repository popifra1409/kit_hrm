<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'requires_end_date',
        'max_duration_months',
        'renewable',
        'is_active',
    ];

    protected $casts = [
        'requires_end_date' => 'boolean',
        'renewable' => 'boolean',
        'is_active' => 'boolean',
        'max_duration_months' => 'integer',
    ];

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }
}
