<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Absence extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'type',
        'date',
        'start_time',
        'end_time',
        'hours',
        'is_full_day',
        'reason',
        'justification_document',
        'status',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'is_paid',
        'deduction_amount',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'approved_at' => 'datetime',
        'is_full_day' => 'boolean',
        'is_paid' => 'boolean',
        'hours' => 'decimal:2',
        'deduction_amount' => 'decimal:2',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Méthodes
    public function approve($approvedBy = null)
    {
        $this->status = 'approved';
        $this->approved_by = $approvedBy ?? auth()->id();
        $this->approved_at = now();
        $this->save();

        return $this;
    }

    public function reject($reason)
    {
        $this->status = 'rejected';
        $this->rejection_reason = $reason;
        $this->approved_by = auth()->id();
        $this->approved_at = now();
        $this->save();

        return $this;
    }

    // Calculer les heures automatiquement
    public function calculateHours()
    {
        if ($this->is_full_day) {
            $this->hours = 8; // Journée complète = 8h
        } elseif ($this->start_time && $this->end_time) {
            $start = \Carbon\Carbon::parse($this->start_time);
            $end = \Carbon\Carbon::parse($this->end_time);
            $this->hours = $end->diffInHours($start, true);
        }

        return $this;
    }

    // Libellés
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'exceptional' => 'Permission Exceptionnelle',
            'personal' => 'Convenance Personnelle',
            'medical' => 'Repos Médical',
            'late_arrival' => 'Retard',
            'early_departure' => 'Départ Anticipé',
            'administrative' => 'Autorisation Administrative',
            default => $this->type,
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'approved' => 'Approuvée',
            'rejected' => 'Rejetée',
            default => $this->status,
        };
    }

    // Accesseur pour le document
    public function getJustificationDocumentUrlAttribute()
    {
        if ($this->justification_document) {
            return \Storage::url($this->justification_document);
        }
        return null;
    }
}
