<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Amendment extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'amendment_number',
        'type',
        'previous_amount',
        'new_amount',
        'variation_amount',
        'variation_percentage',
        'previous_end_date',
        'new_end_date',
        'extension_days',
        'justification',
        'modifications',
        'signature_date',
        'document_path',
        'status',
        'created_by',
    ];

    protected $casts = [
        'previous_amount' => 'decimal:2',
        'new_amount' => 'decimal:2',
        'variation_amount' => 'decimal:2',
        'variation_percentage' => 'decimal:2',
        'previous_end_date' => 'date',
        'new_end_date' => 'date',
        'extension_days' => 'integer',
        'signature_date' => 'date',
    ];

    public function contract()
    {
        return $this->belongsTo(ProcurementContract::class, 'contract_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
