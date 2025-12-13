<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'supplier_id',
        'contract_number',
        'contract_amount',
        'vat_amount',
        'total_amount',
        'signature_date',
        'start_date',
        'end_date',
        'duration_days',
        'performance_bond',
        'advance_payment',
        'warranty_period_months',
        'chuy_representative',
        'supplier_representative',
        'contract_document_path',
        'signed_contract_path',
        'status',
        'notes',
    ];

    protected $casts = [
        'contract_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'performance_bond' => 'decimal:2',
        'advance_payment' => 'decimal:2',
        'signature_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'duration_days' => 'integer',
        'warranty_period_months' => 'integer',
    ];

    public function procurement()
    {
        return $this->belongsTo(Procurement::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function amendments()
    {
        return $this->hasMany(Amendment::class, 'contract_id');
    }

    public function executions()
    {
        return $this->hasMany(ProcurementExecution::class, 'contract_id');
    }

    // Générer un numéro de contrat
    public static function generateContractNumber()
    {
        $year = now()->year;
        $count = self::whereYear('created_at', $year)->count() + 1;
        return 'CONT-CHUY-' . $year . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}
