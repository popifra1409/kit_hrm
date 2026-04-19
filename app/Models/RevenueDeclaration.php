<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RevenueDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'period_id',
        'source',
        'amount',
        'description',
        'declared_by',
        'declared_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'declared_at' => 'datetime',
    ];

    // Relations
    public function period()
    {
        return $this->belongsTo(QuotpartPeriod::class, 'period_id');
    }

    public function declarer()
    {
        return $this->belongsTo(User::class, 'declared_by');
    }

    // Helpers
    public function getSourceNameAttribute()
    {
        return match ($this->source) {
            'consultations' => 'Consultations',
            'hospitalisations' => 'Hospitalisations',
            'pharmacie' => 'Pharmacie',
            'imagerie' => 'Imagerie Médicale',
            'labo' => 'Laboratoire',
            'bloc' => 'Bloc Opératoire',
            'urgences' => 'Urgences',
            'autres' => 'Autres',
            default => $this->source,
        };
    }
}
