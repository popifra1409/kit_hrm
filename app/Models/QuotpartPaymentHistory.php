<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuotpartPaymentHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'distribution_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference',
        'processed_by',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    // Relations
    public function distribution()
    {
        return $this->belongsTo(QuotpartDistribution::class, 'distribution_id');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Helpers
    public function getPaymentMethodNameAttribute()
    {
        return match ($this->payment_method) {
            'virement' => 'Virement Bancaire',
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'mobile_money' => 'Mobile Money',
            default => $this->payment_method,
        };
    }
}
