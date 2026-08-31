<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'code',
        'type',
        'description',
        'direction_id',
        'department_head_id',
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
    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    public function departmentHead()
    {
        return $this->belongsTo(Employee::class, 'department_head_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function parentDepartment()
    {
        return $this->belongsTo(Department::class, 'parent_department_id');
    }

    public function subDepartments()
    {
        return $this->hasMany(Department::class, 'parent_department_id');
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

    public function getTypeNameAttribute()
    {
        return $this->type === 'medical' ? 'Département Médical' : 'Sous-Direction Administrative';
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} ({$this->code})";
    }
}
