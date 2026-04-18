<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Rattachement hiérarchique
        'department_id',        
        'sub_direction_id',    

        // Identification
        'name',
        'code',
        'description',

        // Type de service
        'type', 

        // Responsable unique
        'service_head_id',      

        // Organisation
        'order',
        'is_active',

        // Contact
        'phone',
        'email',
        'location',

        // Anciens champs (à migrer progressivement)
        'head_of_service_id',
        'major_id',
        'service_chief_id',
        'deputy_director_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // ========================================
    // RELATIONS PRINCIPALES
    // ========================================

    /**
     * Département médical (si service médical)
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Sous-direction (si service administratif)
     */
    public function subDirection()
    {
        return $this->belongsTo(SubDirection::class);
    }

    /**
     * Chef de service (relation unifiée)
     */
    public function serviceHead()
    {
        return $this->belongsTo(Employee::class, 'service_head_id');
    }

    /**
     * Secteurs sous ce service
     */
    public function sectors()
    {
        return $this->hasMany(Sector::class)->orderBy('order');
    }

    /**
     * Employés affectés à ce service
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'current_service_id');
    }

    /**
     * Historique des affectations
     */
    public function affectations()
    {
        return $this->hasMany(EmployeeAffectation::class);
    }

    // ========================================
    // ANCIENNES RELATIONS (Migration progressive)
    // ========================================

    /**
     * @deprecated Utiliser serviceHead() à la place
     */
    public function headOfService()
    {
        return $this->belongsTo(Employee::class, 'head_of_service_id');
    }

    /**
     * Major (médical) - à migrer vers serviceHead
     */
    public function major()
    {
        return $this->belongsTo(Employee::class, 'major_id');
    }

    /**
     * @deprecated Utiliser serviceHead() à la place
     */
    public function serviceChief()
    {
        return $this->belongsTo(Employee::class, 'service_chief_id');
    }

    /**
     * @deprecated Utiliser serviceHead() à la place
     */
    public function deputyDirector()
    {
        return $this->belongsTo(Employee::class, 'deputy_director_id');
    }

    /**
     * @deprecated Utiliser department() à la place
     */
    public function medicalDepartment()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Services actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Services médicaux
     */
    public function scopeMedical($query)
    {
        return $query->where('type', 'medical');
    }

    /**
     * Services administratifs
     */
    public function scopeAdministrative($query)
    {
        return $query->where('type', 'administrative');
    }

    /**
     * Services sous un département donné
     */
    public function scopeUnderDepartment($query, $departmentId)
    {
        return $query->where('department_id', $departmentId);
    }

    /**
     * Services sous une sous-direction donnée
     */
    public function scopeUnderSubDirection($query, $subDirectionId)
    {
        return $query->where('sub_direction_id', $subDirectionId);
    }

    // ========================================
    // HELPERS & ACCESSORS
    // ========================================

    /**
     * Obtenir le parent (département ou sous-direction)
     */
    public function getParentAttribute()
    {
        return $this->department ?? $this->subDirection;
    }

    /**
     * Nom du parent
     */
    public function getParentNameAttribute()
    {
        return $this->parent?->name ?? 'N/A';
    }

    /**
     * Type de parent
     */
    public function getParentTypeAttribute()
    {
        if ($this->department_id) {
            return 'Département';
        }
        if ($this->sub_direction_id) {
            return 'Sous-Direction';
        }
        return 'Aucun';
    }

    /**
     * Chemin hiérarchique complet
     */
    public function getHierarchyPathAttribute()
    {
        if ($this->department) {
            // Médical : Département > Service
            return $this->department->name . ' > ' . $this->name;
        }

        if ($this->subDirection) {
            // Administratif : Direction > Sous-Direction > Service
            return $this->subDirection->direction->name . ' > '
                . $this->subDirection->name . ' > '
                . $this->name;
        }

        return $this->name;
    }

    /**
     * Vérifier si service médical
     */
    public function isMedical(): bool
    {
        return $this->type === 'medical' || $this->department_id !== null;
    }

    /**
     * Vérifier si service administratif
     */
    public function isAdministrative(): bool
    {
        return $this->type === 'administrative' || $this->sub_direction_id !== null;
    }

    /**
     * Obtenir le responsable (unifié)
     */
    public function getResponsableAttribute()
    {
        // Priorité : service_head_id, puis migration depuis anciens champs
        return $this->serviceHead
            ?? $this->headOfService
            ?? $this->major
            ?? $this->serviceChief
            ?? $this->deputyDirector;
    }

    /**
     * Nombre d'employés
     */
    public function getEmployeeCountAttribute()
    {
        return $this->employees()->count();
    }

    /**
     * Nombre de secteurs
     */
    public function getSectorCountAttribute()
    {
        return $this->sectors()->count();
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute()
    {
        return match ($this->type) {
            'medical' => '🏥 Médical',
            'administrative' => '🏢 Administratif',
            'support' => '🛠️ Support',
            'technical' => '⚙️ Technique',
            default => 'Non défini',
        };
    }

    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================

    /**
     * Migrer les anciens responsables vers service_head_id
     */
    public function migrateResponsable(): void
    {
        if (!$this->service_head_id) {
            // Ordre de priorité pour la migration
            $this->service_head_id = $this->head_of_service_id
                ?? $this->major_id
                ?? $this->service_chief_id
                ?? $this->deputy_director_id;

            if ($this->service_head_id) {
                $this->save();
            }
        }
    }

    /**
     * Vérifier la cohérence du rattachement
     */
    public function validateParent(): bool
    {
        // Un service doit être rattaché SOIT à un département SOIT à une sous-direction
        $hasDepartment = !is_null($this->department_id);
        $hasSubDirection = !is_null($this->sub_direction_id);

        // XOR : exactement un des deux doit être vrai
        return $hasDepartment !== $hasSubDirection;
    }
}
