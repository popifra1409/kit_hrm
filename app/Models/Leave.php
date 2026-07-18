<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Leave extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'document_path',
        'status',
        'approved_by_n1',
        'approved_by_n2',
        'approved_at_n1',
        'approved_at_n2',
        'rejection_reason',
        'rejected_by',
        'rejected_at',
        'anciennete_score',
        'discipline_score',
        'children_score',
        'total_score',
        'notes',
        'actual_return_date',
        'has_returned',
        'return_confirmed_at',
        'return_confirmed_by',
        'return_notes',
        'is_late_return',
        'late_days',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at_n1' => 'datetime',
        'approved_at_n2' => 'datetime',
        'rejected_at' => 'datetime',
        'total_days' => 'integer',
        'anciennete_score' => 'decimal:2',
        'discipline_score' => 'decimal:2',
        'children_score' => 'decimal:2',
        'total_score' => 'decimal:2',
        'actual_return_date' => 'date',
        'return_confirmed_at' => 'date',
        'has_returned' => 'boolean',
        'is_late_return' => 'boolean',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType()
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function approverN1()
    {
        return $this->belongsTo(User::class, 'approved_by_n1');
    }

    public function approverN2()
    {
        return $this->belongsTo(User::class, 'approved_by_n2');
    }

    public function rejecter()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function returnConfirmedBy()
    {
        return $this->belongsTo(User::class, 'return_confirmed_by');
    }


    public function leaveDecision()
    {
        return $this->belongsTo(LeaveDecision::class);
    }

    // Calculer le score total selon les critères
    public function calculateScore()
    {
        $employee = $this->employee;

        // Score Ancienneté (0-10 points) : 1 point par année
        $anciennete = $employee->recruitment_date ?
            min($employee->recruitment_date->diffInYears(now()), 10) : 0;
        $this->anciennete_score = $anciennete;

        // Score Discipline (0-10 points) : 10 - points disciplinaires
        $discipline = max(0, 10 - $employee->disciplinary_points);
        $this->discipline_score = $discipline;

        // Score Enfants < 6 ans (0-5 points)
        $children = min($employee->children_under_6 ?? 0, 5);
        $this->children_score = $children;

        // Score Total
        $this->total_score = $anciennete + $discipline + $children;

        //$this->save();
    }

    // Méthode pour confirmer le retour
    public function confirmReturn($actualReturnDate = null, $notes = null)
    {
        $this->actual_return_date = $actualReturnDate ?? now();
        $this->has_returned = true;
        $this->return_confirmed_at = now();
        $this->return_confirmed_by = auth()->id();
        $this->return_notes = $notes;

        // Vérifier si retard
        if ($this->actual_return_date->isAfter($this->end_date)) {
            $this->is_late_return = true;
            $this->late_days = $this->end_date->diffInDays($this->actual_return_date);
        }

        $this->save();

        return $this;
    }

    // Boot pour calculer automatiquement les jours
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($leave) {
            if ($leave->start_date && $leave->end_date) {
                $leave->total_days = $leave->start_date->diffInDays($leave->end_date) + 1;
            }
        });
    }
}
