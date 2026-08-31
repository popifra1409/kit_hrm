<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sector extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'service_id',
        'name',
        'code',
        'description',
        'type',
        'sector_head_id',
        'bed_capacity',
        'is_active',
        'order',
        'phone',
        'location'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function sectorHead()
    {
        return $this->belongsTo(Employee::class, 'sector_head_id');
    }

    public function employees()
    {
        return $this->hasMany(Employee::class, 'sector_id');
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

    // Accesseurs
    public function getFullNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }
}
