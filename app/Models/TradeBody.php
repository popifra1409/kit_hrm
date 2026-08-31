<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TradeBody extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'category', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function qualifications()
    {
        return $this->hasMany(Qualification::class);
    }

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Accesseurs
    public function getCategoryLabelAttribute()
    {
        return match ($this->category) {
            'medical' => '🏥 Médical',
            'technical' => '⚙️ Technique',
            'administrative' => '📋 Administratif',
            'support' => '🔧 Support',
            default => $this->category,
        };
    }
}
