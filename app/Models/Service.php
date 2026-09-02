<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'department_id',
        'medical_department_id',
        'service_chief_id',
        'major_id',
        'is_active',
        'sub_direction_id',
        'order',
        'phone',
        'email',
        'location'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function subDirection()
    {
        return $this->belongsTo(SubDirection::class);
    }

    public function serviceChief()
    {
        return $this->belongsTo(Employee::class, 'service_chief_id');
    }

    public function major()
    {
        return $this->belongsTo(Employee::class, 'major_id');
    }

    public function sectors()
    {
        return $this->hasMany(Sector::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'current_service_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }

    /**
     * Responsable "unifié" du service.
     *
     * Les colonnes head_of_service_id / deputy_director_id / service_head_id
     * ont été supprimées (redondantes) : on garde uniquement service_chief_id
     * (services administratifs) et major_id (services médicaux).
     * Cet accesseur permet d'écrire $service->serviceHead sans dépendre
     * d'une relation belongsTo sur une colonne inexistante.
     */
    public function getServiceHeadAttribute()
    {
        return $this->serviceChief ?? $this->major;
    }

    /**
     * Le service est-il de type médical ?
     */
    public function isMedical(): bool
    {
        return $this->type === 'medical';
    }

    /**
     * Le service est-il de type administratif (administratif, support ou technique) ?
     */
    public function isAdministrative(): bool
    {
        return in_array($this->type, ['administrative', 'support', 'technical']);
    }
}
