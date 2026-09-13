<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CensusSubmission extends Model
{
    protected $fillable = [
        'census_campaign_id',
        'employee_id',
        'status',
        'payload',
        'submitted_at',
        'validated_by',
        'validated_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
    ];

    protected $casts = [
        'payload' => 'array',
        'submitted_at' => 'datetime',
        'validated_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(CensusCampaign::class, 'census_campaign_id');
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function isValidated(): bool
    {
        return $this->status === 'validated';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
