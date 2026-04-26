<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Direction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'acronym',
        'description',
        'director_id',
        'type',
        'order',
        'is_active',
        'phone',
        'email',
        'location',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Relations
    public function director()
    {
        return $this->belongsTo(Employee::class, 'director_id');
    }

    public function subDirections()
    {
        return $this->hasMany(SubDirection::class)->orderBy('order');
    }

    /**
     * Départements médicaux rattachés à cette direction
     */
    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function employees()
    {
        return $this->hasManyThrough(Employee::class, SubDirection::class, 'direction_id', 'current_service_id');
    }

    /**
     * Compte total des sous-directions (administratives + départements médicaux)
     */
    public function getTotalSubstructuresCountAttribute()
    {
        return $this->subDirections()->count() + $this->departments()->count();
    }

    /**
     * Toutes les sous-structures (sous-directions + départements)
     */
    public function allSubstructures()
    {
        return collect()
            ->merge($this->subDirections)
            ->merge($this->departments);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Helpers
    public function getEmployeeCountAttribute()
    {
        return $this->subDirections->sum(fn($sd) => $sd->services->sum('employees_count'));
    }
}
