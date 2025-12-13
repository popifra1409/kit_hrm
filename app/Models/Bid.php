<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bid extends Model
{
    use HasFactory;

    protected $fillable = [
        'procurement_id',
        'supplier_id',
        'reference',
        'bid_amount',
        'bid_amount_ht',
        'bid_amount_ttc',
        'vat_amount',
        'execution_period',
        'warranty_period',
        'submitted_at',
        'submitted_by',
        'is_late',
        'is_technically_compliant',
        'technical_compliance_notes',
        'is_financially_compliant',
        'financial_compliance_notes',
        'has_required_documents',
        'missing_documents',
        'status',
        'total_score',
        'rank',
        'notes',
    ];

    protected $casts = [
        'bid_amount' => 'decimal:2',
        'bid_amount_ht' => 'decimal:2',
        'bid_amount_ttc' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'execution_period' => 'integer',
        'warranty_period' => 'integer',
        'submitted_at' => 'datetime',
        'is_late' => 'boolean',
        'is_technically_compliant' => 'boolean',
        'is_financially_compliant' => 'boolean',
        'has_required_documents' => 'boolean',
        'total_score' => 'decimal:2',
        'rank' => 'integer',
    ];

    public function procurement()
    {
        return $this->belongsTo(Procurement::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function evaluations()
    {
        return $this->hasMany(BidEvaluation::class);
    }

    // Calculer le score total à partir des évaluations
    public function calculateTotalScore()
    {
        $avgScore = $this->evaluations()->avg('total_score');
        $this->total_score = $avgScore;
        $this->save();
        return $avgScore;
    }

    // Générer une référence unique
    public static function generateReference($procurementId)
    {
        $procurement = Procurement::find($procurementId);
        $count = self::where('procurement_id', $procurementId)->count() + 1;
        return $procurement->reference . '/OFFRE-' . str_pad($count, 2, '0', STR_PAD_LEFT);
    }
}
