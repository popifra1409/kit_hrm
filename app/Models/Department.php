<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        // Identification
        'name',
        'code',
        'description',

        // Type médical
        'type', // medical, surgical, diagnostic, support

        // Responsable (Chef de Département = rang Sous-Directeur)
        'direction_id',
        'department_head_id',

        // Niveau hiérarchique (toujours "sub_direction" pour départements médicaux)
        'hierarchical_level',

        // Organisation
        'order',
        'is_active',

        // Contact
        'phone',
        'email',
        'location',

        // Anciens champs (migration progressive)
        'parent_department_id',
        'director_id',
        'level',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'level' => 'integer', // Déprécié mais gardé pour compatibilité
    ];

    // ========================================
    // RELATIONS PRINCIPALES
    // ========================================

    /**
     * Direction de rattachement
     */
    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    /**
     * Chef de département (équivalent Sous-Directeur niveau médical)
     */
    public function departmentHead()
    {
        return $this->belongsTo(Employee::class, 'department_head_id');
    }

    /**
     * Services médicaux sous ce département
     */
    public function services()
    {
        return $this->hasMany(Service::class)->orderBy('order');
    }

    /**
     * Employés affectés directement au département
     */
    public function employees()
    {
        return $this->hasMany(Employee::class, 'department_id');
    }

    /**
     * Tous les employés (département + services)
     */
    public function allEmployees()
    {
        return Employee::where('department_id', $this->id)
            ->orWhereHas('service', function ($q) {
                $q->where('department_id', $this->id);
            });
    }

    // ========================================
    // ANCIENNES RELATIONS (Migration progressive)
    // ========================================

    /**
     * @deprecated Dans la nouvelle structure, les départements n'ont plus de hiérarchie parent/enfant
     */
    public function parentDepartment()
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    /**
     * @deprecated Dans la nouvelle structure, les départements n'ont plus de sous-départements
     */
    public function subDepartments()
    {
        return $this->hasMany(Department::class, 'parent_department_id');
    }

    /**
     * @deprecated Utiliser departmentHead() à la place
     */
    public function director()
    {
        return $this->belongsTo(Employee::class, 'director_id');
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Départements actifs uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Départements médicaux
     */
    public function scopeMedical($query)
    {
        return $query->where('type', 'medical');
    }

    /**
     * Départements chirurgicaux
     */
    public function scopeSurgical($query)
    {
        return $query->where('type', 'surgical');
    }

    /**
     * Départements de diagnostic
     */
    public function scopeDiagnostic($query)
    {
        return $query->where('type', 'diagnostic');
    }

    /**
     * Départements support
     */
    public function scopeSupport($query)
    {
        return $query->where('type', 'support');
    }

    /**
     * Ordonner par ordre d'affichage
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // ========================================
    // HELPERS & ACCESSORS
    // ========================================

    /**
     * Obtenir le responsable (unifié)
     */
    public function getResponsableAttribute()
    {
        // Priorité : department_head_id, puis migration depuis director_id
        return $this->departmentHead ?? $this->director;
    }

    /**
     * Nom du responsable
     */
    public function getResponsableNameAttribute()
    {
        return $this->responsable?->full_name ?? 'Non assigné';
    }

    /**
     * Titre du poste du responsable
     */
    public function getResponsableTitleAttribute()
    {
        return 'Chef de Département'; // Équivalent Sous-Directeur
    }

    /**
     * Nombre de services sous ce département
     */
    public function getServiceCountAttribute()
    {
        return $this->services()->count();
    }

    /**
     * Nombre total d'employés (département + services)
     */
    public function getTotalEmployeeCountAttribute()
    {
        $directEmployees = $this->employees()->count();
        $serviceEmployees = $this->services()->withCount('employees')->get()->sum('employees_count');

        return $directEmployees + $serviceEmployees;
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute()
    {
        return match ($this->type) {
            'medical' => '🏥 Médecine',
            'surgical' => '⚕️ Chirurgie',
            'diagnostic' => '🔬 Diagnostic',
            'support' => '🛠️ Support',
            default => 'Médical',
        };
    }

    /**
     * Badge couleur selon le type
     */
    public function getTypeColorAttribute()
    {
        return match ($this->type) {
            'medical' => 'info',
            'surgical' => 'danger',
            'diagnostic' => 'warning',
            'support' => 'success',
            default => 'gray',
        };
    }

    /**
     * Niveau hiérarchique (toujours sous-direction pour départements)
     */
    public function getHierarchicalLevelLabelAttribute()
    {
        return 'Sous-Direction (Département Médical)';
    }

    /**
     * Chemin hiérarchique complet
     */
    public function getHierarchyPathAttribute()
    {
        // Dans la nouvelle structure : Direction Médicale > Département
        return 'Direction des Affaires Médicales > ' . $this->name;
    }

    /**
     * Capacité totale en lits (pour départements médicaux)
     */
    public function getTotalBedCapacityAttribute()
    {
        return $this->services()
            ->with('sectors')
            ->get()
            ->flatMap->sectors
            ->sum('bed_capacity') ?? 0;
    }

    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================

    /**
     * Migrer l'ancien director_id vers department_head_id
     */
    public function migrateDirector(): void
    {
        if (!$this->department_head_id && $this->director_id) {
            $this->department_head_id = $this->director_id;
            $this->save();
        }
    }

    /**
     * Définir le niveau hiérarchique par défaut
     */
    public function setDefaultHierarchicalLevel(): void
    {
        if (!$this->hierarchical_level) {
            $this->hierarchical_level = 'sub_direction';
            $this->save();
        }
    }

    /**
     * Vérifier si le département a des services
     */
    public function hasServices(): bool
    {
        return $this->services()->exists();
    }

    /**
     * Vérifier si le département a un responsable
     */
    public function hasResponsable(): bool
    {
        return !is_null($this->department_head_id) || !is_null($this->director_id);
    }

    /**
     * Obtenir tous les postes disponibles dans ce département
     */
    public function getAvailablePositions()
    {
        return Position::where('hierarchical_level', 'IN', [
            'chef_service',
            'major',
            'cadre',
            'agent'
        ])->get();
    }

    /**
     * Statistiques du département
     */
    public function getStats(): array
    {
        return [
            'services' => $this->service_count,
            'employees' => $this->total_employee_count,
            'bed_capacity' => $this->total_bed_capacity,
            'responsable' => $this->responsable_name,
            'type' => $this->type_label,
        ];
    }

    // ========================================
    // ÉVÉNEMENTS DU MODÈLE
    // ========================================

    protected static function boot()
    {
        parent::boot();

        // Définir le niveau hiérarchique par défaut lors de la création
        static::creating(function ($department) {
            if (!$department->hierarchical_level) {
                $department->hierarchical_level = 'sub_direction';
            }

            // Si pas d'ordre défini, mettre à la fin
            if (!$department->order) {
                $department->order = self::max('order') + 1;
            }
        });

        // Lors de la suppression, détacher les services
        static::deleting(function ($department) {
            // Optionnel : détacher ou réassigner les services
            // $department->services()->update(['department_id' => null]);
        });
    }
}
