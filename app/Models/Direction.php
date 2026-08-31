<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Direction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'director_id',
        'is_active',
        'order',
        'phone',
        'email',
        'location'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function director()
    {
        return $this->belongsTo(Employee::class, 'director_id');
    }

    public function departments()
    {
        return $this->hasMany(Department::class);
    }

    public function subDirections()
    {
        return $this->hasMany(SubDirection::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeMedical($query)
    {
        return $query->where('type', 'medical');
    }

    public function scopeAdministrative($query)
    {
        return $query->where('type', 'administrative');
    }

    // Accesseurs
    public function getTypeNameAttribute()
    {
        return $this->type === 'medical' ? '🏥 Médicale' : '📋 Administrative';
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }

    public function isMedical()
    {
        return $this->type === 'medical';
    }

    public function isAdministrative()
    {
        return $this->type === 'administrative';
    }
}
