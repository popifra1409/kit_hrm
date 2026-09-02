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
        'gender',
        'classification_type',
        'category_recruitment',
        'category_current',
        'category_number',
        'echelon_number',
        'indice',
        'trade_body_id',
        'qualification_id',
        'job_title_id',
        'department_id',
        'current_service_id',
        'service_id',
        'sector_id',
        'administrative_status',
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
        'status',
        'is_active',
        'current_echelon',
        'echelon_start_date',
        'last_advancement_date',

        // Champs assurance santé et biométrie
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
        // 'category_number' => 'integer',
        // 'echelon_number' => 'integer',
        'indice' => 'integer',
        'retirement_age' => 'integer',
        'children_under_6' => 'integer',
        'total_children' => 'integer',
        'current_echelon' => 'integer',
        'echelon_start_date' => 'date',
        'last_advancement_date' => 'date',
        'fingerprint_enrolled' => 'boolean',
        'fingerprint_enrolled_at' => 'datetime',
    ];

    // ========================================
    // ACCESSORS
    // ========================================

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->last_name . ' ' . $this->first_name,
        );
    }

    // ========================================
    // RELATIONS - STRUCTURE ORGANISATIONNELLE
    // ========================================

    public function tradeBody()
    {
        return $this->belongsTo(TradeBody::class);
    }

    public function jobTitle()
    {
        return $this->belongsTo(JobTitle::class);
    }

    public function qualification()
    {
        return $this->belongsTo(Qualification::class);
    }

    /**
     * Département (médical uniquement)
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Service actuel (médical ou administratif)
     */
    public function currentService()
    {
        return $this->belongsTo(Service::class, 'current_service_id');
    }

    /**
     * Service d'origine
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Secteur/Unité (nouveau niveau)
     */
    public function sector()
    {
        return $this->belongsTo(Sector::class);
    }

    // Services managés par cet employé
    public function managedServices()
    {
        return $this->hasMany(Service::class, 'service_chief_id');
    }

    // Services où il est major
    public function majorServices()
    {
        return $this->hasMany(Service::class, 'major_id');
    }

    // Unités managées
    public function managedSectors()
    {
        return $this->hasMany(Sector::class, 'sector_head_id');
    }

    // Département managé
    public function managedDepartment()
    {
        return $this->hasOne(Department::class, 'department_head_id');
    }

    // Direction managée
    public function managedDirection()
    {
        return $this->hasOne(Direction::class, 'director_id');
    }

    /**
     * Obtenir la Direction via le service actuel
     */
    public function getDirectionAttribute()
    {
        if ($this->currentService && $this->currentService->subDirection) {
            return $this->currentService->subDirection->direction;
        }
        return null;
    }

    /**
     * Obtenir la Sous-Direction via le service actuel
     */
    public function getSubDirectionAttribute()
    {
        if ($this->currentService) {
            return $this->currentService->subDirection;
        }
        return null;
    }

    /**
     * Chemin hiérarchique complet
     */
    public function getHierarchyPathAttribute()
    {
        $path = [];

        // Si service administratif
        if ($this->currentService && $this->currentService->subDirection) {
            $direction = $this->currentService->subDirection->direction;
            $path[] = $direction->name;
            $path[] = $this->currentService->subDirection->name;
            $path[] = $this->currentService->name;
        }
        // Si service médical
        elseif ($this->currentService && $this->currentService->department) {
            $path[] = $this->currentService->department->name;
            $path[] = $this->currentService->name;
        }
        // Si département seul
        elseif ($this->department) {
            $path[] = $this->department->name;
        }

        // Ajouter le secteur si présent
        if ($this->sector) {
            $path[] = $this->sector->name;
        }

        return implode(' > ', $path);
    }

    /**
     * Niveau hiérarchique (unifié sur JobTitle, Position supprimé)
     */
    public function getHierarchyLevelAttribute()
    {
        return $this->jobTitle?->hierarchy_level ?? 999;
    }

    public function getIsMedicalAttribute()
    {
        return $this->personnel_type === 'soignant' ||
            $this->tradeBody?->category === 'medical';
    }

    public function getAdministrativeStatusLabelAttribute(): string
    {
        return match ($this->administrative_status) {
            'fonctionnaire_affecte' => 'Fonctionnaire Affecté',
            'fonctionnaire_detache' => 'Fonctionnaire en Détachement',
            'contractuel_fp' => 'Contractuel de la Fonction Publique',
            'contractuel_structure' => 'Contractuel de la Structure',
            'stagiaire' => 'Stagiaire',
            default => 'Non défini',
        };
    }

    public function getIsManagerialAttribute()
    {
        return $this->jobTitle?->is_managerial ?? false;
    }

    public function getFullClassificationAttribute()
    {
        return "{$this->qualification?->name} - {$this->tradeBody?->name}";
    }

    // ========================================
    // RELATIONS - RH & CONTRATS
    // ========================================

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

    // ========================================
    // RELATIONS - CONGÉS & PAIE
    // ========================================

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

    // ========================================
    // RELATIONS - HISTORIQUES
    // ========================================

    public function assignmentHistory()
    {
        return $this->hasMany(EmployeeAssignmentHistory::class)->orderBy('effective_date', 'desc');
    }

    public function advancementHistory()
    {
        return $this->hasMany(EmployeeAdvancementHistory::class)->orderBy('effective_date', 'desc');
    }

    // ========================================
    // RELATIONS - ASSURANCE SANTÉ
    // ========================================

    public function dependents()
    {
        return $this->hasMany(Dependent::class);
    }

    public function employeeCards()
    {
        return $this->hasMany(EmployeeCard::class);
    }

    // ========================================
    // HELPERS - CARTES
    // ========================================

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

    // ========================================
    // HELPERS - AYANTS DROIT
    // ========================================

    public function getActiveDependentsCount(): int
    {
        return $this->dependents()->where('is_active', true)->count();
    }

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

    // ========================================
    // HELPERS - CALCULS
    // ========================================

    public function getAncienneteAttribute()
    {
        return $this->recruitment_date ? $this->recruitment_date->diffInYears(now()) : 0;
    }

    public function getAgeAttribute()
    {
        return $this->birth_date ? $this->birth_date->diffInYears(now()) : 0;
    }

    public function getBaseSalaryFromGrid()
    {
        $category = $this->category_current ?? $this->category_number;
        $echelon = $this->current_echelon ?? $this->echelon_number;

        if (!$category || !$echelon) {
            return 0;
        }

        return SalaryGrid::getBaseSalary($category, $echelon);
    }

    // ========================================
    // HELPERS - BIOMÉTRIE & QR CODE
    // ========================================

    public function hasFingerprintEnrolled(): bool
    {
        return $this->fingerprint_enrolled && !empty($this->fingerprint_data);
    }

    public function getQrCodeUrl(): ?string
    {
        if (!$this->qr_code_path) {
            return null;
        }

        return \Storage::url($this->qr_code_path);
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        return $this->qr_code_path ? \Storage::url($this->qr_code_path) : null;
    }

    // ========================================
    // HELPERS - HIÉRARCHIE
    // ========================================

    /**
     * Vérifier si l'employé est dans la branche administrative
     */
    public function isAdministrative(): bool
    {
        return $this->currentService && $this->currentService->isAdministrative();
    }

    /**
     * Vérifier si l'employé est dans la branche médicale
     */
    public function isMedical(): bool
    {
        return $this->currentService && $this->currentService->isMedical()
            || $this->department !== null;
    }

    /**
     * Vérifier si c'est un poste de management (Position supprimé -> basé sur JobTitle)
     */
    public function isManager(): bool
    {
        return $this->jobTitle?->is_managerial ?? false;
    }

    /**
     * Obtenir tous les subordonnés (si manager).
     * Basé sur les relations "managed*" plutôt que sur un libellé de Position,
     * pour ne plus dépendre du modèle Position supprimé.
     */
    public function getSubordinates()
    {
        if (!$this->isManager()) {
            return collect([]);
        }

        // Chef de service / Major : employés de ses services gérés
        $serviceIds = $this->managedServices()->pluck('id')
            ->merge($this->majorServices()->pluck('id'));

        if ($serviceIds->isNotEmpty()) {
            return Employee::whereIn('current_service_id', $serviceIds)
                ->where('id', '!=', $this->id)
                ->get();
        }

        // Chef de secteur : employés de ses secteurs gérés
        $sectorIds = $this->managedSectors()->pluck('id');

        if ($sectorIds->isNotEmpty()) {
            return Employee::whereIn('sector_id', $sectorIds)
                ->where('id', '!=', $this->id)
                ->get();
        }

        // Chef de département : employés des services du département
        if ($this->managedDepartment) {
            return Employee::whereHas(
                'currentService',
                fn($q) =>
                $q->where('department_id', $this->managedDepartment->id)
            )
                ->where('id', '!=', $this->id)
                ->get();
        }

        return collect([]);
    }

    // ========================================
    // BOOT
    // ========================================

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
