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
        'is_active',
        'requires_end_date',
        'max_duration_months',
        'renewable',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_end_date' => 'boolean',
        'max_duration_months' => 'integer',
        'renewable' => 'boolean',
    ];

    /**
     * Employés ayant ce type de contrat
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'contract_type_id');
    }
}
