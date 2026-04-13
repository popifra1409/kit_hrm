<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'registration_number',
        'tax_number',
        'armp_number',
        'supplier_type',
        'category',
        'address',
        'city',
        'country',
        'phone',
        'email',
        'contact_person',
        'contact_phone',
        'specialties',
        'status',
        'performance_score',
        'notes',
    ];

    protected $casts = [
        'specialties' => 'array',
        'performance_score' => 'decimal:2',
    ];

    // Relations
    public function procurements()
    {
        return $this->hasMany(Procurement::class, 'awarded_supplier_id');
    }

    public function bids()
    {
        return $this->hasMany(Bid::class);
    }

    public function contracts()
    {
        return $this->hasMany(ProcurementContract::class);
    }
}
