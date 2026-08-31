<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobTitle extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'level', 'hierarchy_level', 'is_managerial', 'is_active'];

    protected $casts = [
        'is_managerial' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relations
    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeManagerial($query)
    {
        return $query->where('is_managerial', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }

    // Accesseurs
    public function getLevelLabelAttribute()
    {
        return match ($this->level) {
            'president' => '👑 Président du Conseil',
            'director_general' => '📊 Directeur Général',
            'director_general_adjoint' => '📊 Directeur Général Adjoint',
            'director' => '📋 Directeur',
            'chief_department' => '🏢 Chef de Département/Sous-Direction',
            'chief_service' => '🏥 Chef de Service',
            'major' => '🎖️ Major',
            'chief_unit' => '⚙️ Chef d\'Unité',
            'employee' => '👤 Employé',
            default => $this->level,
        };
    }
}
