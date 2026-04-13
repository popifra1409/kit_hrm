<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'department_id',
        'medical_department_id',
        'head_of_service_id',
        'major_id',
        'service_chief_id',
        'deputy_director_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // RELATIONS - Vérifiez que toutes sont présentes
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function medicalDepartment()
    {
        return $this->belongsTo(MedicalDepartment::class);
    }

    public function headOfService()
    {
        return $this->belongsTo(Employee::class, 'head_of_service_id');
    }

    public function major()
    {
        return $this->belongsTo(Employee::class, 'major_id');
    }

    public function serviceChief()
    {
        return $this->belongsTo(Employee::class, 'service_chief_id');
    }

    public function deputyDirector()
    {
        return $this->belongsTo(Employee::class, 'deputy_director_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'current_service_id');
    }

    public function affectations()
    {
        return $this->hasMany(EmployeeAffectation::class);
    }
}
