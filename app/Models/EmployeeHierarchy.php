<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeHierarchy extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'organization_level_id',
        'superior_id',
        'department_id',
        'medical_department_id',
        'service_id',
        'start_date',
        'end_date',
        'is_current',
        'appointment_decision',
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

    public function organizationLevel()
    {
        return $this->belongsTo(OrganizationLevel::class);
    }

    public function superior()
    {
        return $this->belongsTo(Employee::class, 'superior_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function medicalDepartment()
    {
        return $this->belongsTo(MedicalDepartment::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
