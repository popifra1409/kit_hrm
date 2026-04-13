<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrganizationLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'hierarchy_level',
        'branch',
        'description',
        'is_active',
    ];

    protected $casts = [
        'hierarchy_level' => 'integer',
        'is_active' => 'boolean',
    ];

    public function employeeHierarchies()
    {
        return $this->hasMany(EmployeeHierarchy::class);
    }
}
