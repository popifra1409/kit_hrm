<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SubDirection extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'direction_id',
        'name',
        'code',
        'acronym',
        'description',
        'sub_director_id',
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
    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    public function subDirector()
    {
        return $this->belongsTo(Employee::class, 'sub_director_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class)->orderBy('order');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
