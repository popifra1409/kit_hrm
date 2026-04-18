<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Position extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'description',
        'hierarchical_level',
        'level_rank',          
        'is_managerial',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_managerial' => 'boolean',
        'level_rank' => 'integer',
    ];

    // ========================================
    // RELATIONS
    // ========================================

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    // ========================================
    // SCOPES
    // ========================================

    /**
     * Positions actives uniquement
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Positions de management
     */
    public function scopeManagerial($query)
    {
        return $query->where('is_managerial', true);
    }

    /**
     * Positions par niveau hiérarchique
     */
    public function scopeByLevel($query, $level)
    {
        return $query->where('hierarchical_level', $level);
    }

    /**
     * Ordonner par rang hiérarchique
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('level_rank')->orderBy('name');
    }

    // ========================================
    // HELPERS & ACCESSORS
    // ========================================

    /**
     * Obtenir le libellé du niveau hiérarchique
     */
    public function getHierarchicalLevelLabelAttribute()
    {
        return match ($this->hierarchical_level) {
            'pca' => 'Président du Conseil d\'Administration',
            'dg' => 'Directeur Général',
            'dga' => 'Directeur Général Adjoint',
            'directeur' => 'Directeur',
            'sous_directeur' => 'Sous-Directeur',
            'chef_service' => 'Chef de Service',
            'major' => 'Major',
            'chef_secteur' => 'Chef de Secteur',
            'cadre' => 'Cadre',
            'agent' => 'Agent',
            'stagiaire' => 'Stagiaire',
            default => 'Non défini',
        };
    }

    /**
     * Vérifier si la position est de direction
     */
    public function isDirection(): bool
    {
        return in_array($this->hierarchical_level, ['pca', 'dg', 'dga', 'directeur']);
    }

    /**
     * Vérifier si la position est de niveau intermédiaire
     */
    public function isMiddleManagement(): bool
    {
        return in_array($this->hierarchical_level, ['sous_directeur', 'chef_service']);
    }

    /**
     * Vérifier si la position est opérationnelle
     */
    public function isOperational(): bool
    {
        return in_array($this->hierarchical_level, ['major', 'chef_secteur', 'cadre', 'agent']);
    }

    /**
     * Obtenir la couleur du badge selon le niveau
     */
    public function getLevelColorAttribute()
    {
        return match ($this->level_rank) {
            1, 2, 3 => 'danger',      // Exécutif
            4 => 'primary',            // Direction
            5 => 'warning',            // Sous-Direction
            6 => 'info',               // Service
            7 => 'success',            // Secteur
            8, 9 => 'gray',            // Opérationnel
            10 => 'secondary',         // Stagiaire
            default => 'gray',
        };
    }

    /**
     * Nombre d'employés à ce poste
     */
    public function getEmployeeCountAttribute()
    {
        return $this->employees()->count();
    }
}
