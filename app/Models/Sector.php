<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Sector extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'service_id',
        'name',
        'code',
        'description',
        'type',
        'sector_head_id',
        'head_title',
        'bed_capacity',
        'order',
        'is_active',
        'phone',
        'location',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'bed_capacity' => 'integer',
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

    // Helpers
    public function getHeadTitleLabelAttribute()
    {
        return match ($this->head_title) {
            'chef_secteur' => 'Chef de Secteur',
            'major' => 'Major',
            'responsable' => 'Responsable',
            default => 'Chef de Secteur',
        };
    }
}
