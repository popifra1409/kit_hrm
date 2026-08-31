<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Qualification extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'code', 'description', 'trade_body_id', 'level_rank', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Relations
    public function tradeBody(): BelongsTo
    {
        return $this->belongsTo(TradeBody::class);
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

    public function scopeByTradeBody($query, $tradeBodyId)
    {
        return $query->where('trade_body_id', $tradeBodyId);
    }

    // Accesseurs
    public function getFullNameAttribute()
    {
        return "{$this->name} ({$this->tradeBody?->name})";
    }
}
