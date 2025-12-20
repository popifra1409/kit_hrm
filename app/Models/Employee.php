<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Employee extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'matricule',
        'first_name',
        'last_name',
        'category_recruitment',
        'category_current',
        'category_number',
        'echelon_number',
        'qualification',
        'department_id',
        'position_id',
        'current_service_id',
        'service_id', 
        'employment_type',
        'contract_type_id',
        'personnel_type',
        'birth_date',
        'recruitment_date',
        'service_start_date',
        'retirement_date',
        'retirement_age',
        'contract_number',
        'bank_account_number',
        'bank_name',
        'phone',
        'email',
        'address',
        'city',
        'marital_status',
        'children_under_6',
        'total_children',
        'disciplinary_points',
        'disciplinary_notes',
        'status',
        'is_active',
        'current_echelon',
        'echelon_start_date',
        'last_advancement_date',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'recruitment_date' => 'date',
        'service_start_date' => 'date',
        'retirement_date' => 'date',
        'is_active' => 'boolean',
        'category_number' => 'integer',
        'echelon_number' => 'integer',
        'retirement_age' => 'integer',
        'children_under_6' => 'integer',
        'total_children' => 'integer',
        'disciplinary_points' => 'integer',
        'current_echelon' => 'integer',
        'echelon_start_date' => 'date',
        'last_advancement_date' => 'date',
    ];

    // Accessor pour le nom complet
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->last_name . ' ' . $this->first_name,
        );
    }

    // Relations
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function position()
    {
        return $this->belongsTo(Position::class);
    }

    public function currentService()
    {
        return $this->belongsTo(Service::class, 'current_service_id');
    }

     public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function contractType()
    {
        return $this->belongsTo(ContractType::class);
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function affectations()
    {
        return $this->hasMany(EmployeeAffectation::class);
    }

    public function currentAffectation()
    {
        return $this->hasOne(EmployeeAffectation::class)->where('is_current', true);
    }

    public function hierarchies()
    {
        return $this->hasMany(EmployeeHierarchy::class);
    }

    public function currentHierarchy()
    {
        return $this->hasOne(EmployeeHierarchy::class)->where('is_current', true);
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class);
    }

    public function leaveBalances()
    {
        return $this->hasMany(LeaveBalance::class);
    }

    // Relations pour l'historique
    public function assignmentHistory()
    {
        return $this->hasMany(EmployeeAssignmentHistory::class)->orderBy('effective_date', 'desc');
    }

    public function advancementHistory()
    {
        return $this->hasMany(EmployeeAdvancementHistory::class)->orderBy('effective_date', 'desc');
    }

    // Méthode pour calculer l'ancienneté
    public function getAncienneteAttribute()
    {
        return $this->recruitment_date ? $this->recruitment_date->diffInYears(now()) : 0;
    }

    // Méthode pour calculer l'âge
    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->diffInYears(now()) : 0;
    }

    // Boot method pour calculer automatiquement la date de retraite
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($employee) {
            if ($employee->birth_date && $employee->retirement_age) {
                $employee->retirement_date = $employee->birth_date->copy()->addYears($employee->retirement_age);
            }
        });
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    // Méthode pour obtenir le salaire de base depuis la grille
    public function getBaseSalaryFromGrid()
    {
        if (!$this->category_number || !$this->echelon_number) {
            return 0;
        }

        return SalaryGrid::getBaseSalary($this->category_number, $this->echelon_number);
    }
}
