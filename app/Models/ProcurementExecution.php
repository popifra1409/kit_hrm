<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProcurementExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'report_date',
        'progress_percentage',
        'amount_executed',
        'amount_paid',
        'is_on_schedule',
        'delay_days',
        'quality_rating',
        'has_issues',
        'issues_description',
        'corrective_actions',
        'observations',
        'recommendations',
        'reported_by',
    ];

    protected $casts = [
        'report_date' => 'date',
        'progress_percentage' => 'decimal:2',
        'amount_executed' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'is_on_schedule' => 'boolean',
        'delay_days' => 'integer',
        'has_issues' => 'boolean',
    ];

    public function contract()
    {
        return $this->belongsTo(ProcurementContract::class, 'contract_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
