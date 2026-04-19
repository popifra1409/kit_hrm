<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotpartParameter extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'code',
        'name',
        'description',
        'applies_to',
        'weight',
        'min_value',
        'max_value',
        'is_active',
        'order',
    ];

    protected $casts = [
        'weight' => 'decimal:4',
        'min_value' => 'decimal:2',
        'max_value' => 'decimal:2',
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('name');
    }

    // Helpers
    public function appliesTo($personnelType)
    {
        if (!$this->applies_to || $this->applies_to === 'all') {
            return true;
        }
        return $this->applies_to === $personnelType;
    }
}
