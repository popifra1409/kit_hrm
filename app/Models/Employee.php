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

        // Nouveaux champs pour le module assurance santé et biométrie
        'photo',
        'qr_code_path',
        'qr_code_data',
        'fingerprint_data',
        'fingerprint_enrolled',
        'fingerprint_enrolled_at',

        'id_card_number',
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

        // Nouveaux casts
        'fingerprint_enrolled' => 'boolean',
        'fingerprint_enrolled_at' => 'datetime',
    ];

    // Accessor pour le nom complet
    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->last_name . ' ' . $this->first_name,
        );
    }

    // Relations de base
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

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
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

    // Relations pour le module assurance santé
    public function dependents()
    {
        return $this->hasMany(Dependent::class);
    }

    public function employeeCards()
    {
        return $this->hasMany(EmployeeCard::class);
    }

    // Méthodes helper pour les cartes
    public function hasActiveHealthCard(): bool
    {
        return $this->employeeCards()
            ->where('card_type', 'health_coverage')
            ->where('is_active', true)
            ->exists();
    }

    public function hasActiveProfessionalCard(): bool
    {
        return $this->employeeCards()
            ->where('card_type', 'professional')
            ->where('is_active', true)
            ->exists();
    }

    public function getActiveDependentsCount(): int
    {
        return $this->dependents()->where('is_active', true)->count();
    }

    // Méthodes helper pour les ayants droit
    public function getSpouse()
    {
        return $this->dependents()
            ->where('relationship', 'spouse')
            ->where('is_alive', true)
            ->first();
    }

    public function getChildren()
    {
        return $this->dependents()
            ->where('relationship', 'child')
            ->where('is_alive', true)
            ->get();
    }

    public function getParents()
    {
        return $this->dependents()
            ->whereIn('relationship', ['father', 'mother'])
            ->where('is_alive', true)
            ->get();
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

    // Méthode pour obtenir le salaire de base depuis la grille
    public function getBaseSalaryFromGrid()
    {
        // Utiliser current_echelon et category_current en priorité
        $category = $this->category_current ?? $this->category_number;
        $echelon = $this->current_echelon ?? $this->echelon_number;

        if (!$category || !$echelon) {
            return 0;
        }

        return SalaryGrid::getBaseSalary($category, $echelon);
    }

    // Méthode pour vérifier si l'employé a des empreintes enregistrées
    public function hasFingerprintEnrolled(): bool
    {
        return $this->fingerprint_enrolled && !empty($this->fingerprint_data);
    }

    // Méthode pour obtenir le QR Code URL
    public function getQrCodeUrl(): ?string
    {
        if (!$this->qr_code_path) {
            return null;
        }

        return \Storage::url($this->qr_code_path);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($employee) {
            // Calculer automatiquement la date de retraite
            if ($employee->birth_date && $employee->retirement_age) {
                $employee->retirement_date = $employee->birth_date->copy()->addYears($employee->retirement_age);
            }
        });
    }
}
