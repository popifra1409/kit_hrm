<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'date',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'break_duration',
        'total_hours',
        'regular_hours',
        'overtime_hours',
        'status',
        'is_late',
        'late_minutes',
        'is_early_departure',
        'early_departure_minutes',
        'notes',
        'justification_document',
        'is_validated',
        'validated_by',
        'validated_at',
        'clock_in_location',
        'clock_out_location',
        'clock_in_ip',
        'clock_out_ip',
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime:H:i',
        'clock_out' => 'datetime:H:i',
        'break_start' => 'datetime:H:i',
        'break_end' => 'datetime:H:i',
        'validated_at' => 'datetime',
        'is_late' => 'boolean',
        'is_early_departure' => 'boolean',
        'is_validated' => 'boolean',
        'break_duration' => 'decimal:2',
        'total_hours' => 'decimal:2',
        'regular_hours' => 'decimal:2',
        'overtime_hours' => 'decimal:2',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function validatedBy()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // Méthodes de calcul
    public function calculateHours()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return $this;
        }

        $clockIn = Carbon::parse($this->clock_in);
        $clockOut = Carbon::parse($this->clock_out);

        // Calculer le temps total
        $totalMinutes = $clockOut->diffInMinutes($clockIn);

        // Soustraire la pause
        if ($this->break_start && $this->break_end) {
            $breakStart = Carbon::parse($this->break_start);
            $breakEnd = Carbon::parse($this->break_end);
            $breakMinutes = $breakEnd->diffInMinutes($breakStart);
            $this->break_duration = round($breakMinutes / 60, 2);
            $totalMinutes -= $breakMinutes;
        }

        // Convertir en heures
        $this->total_hours = round($totalMinutes / 60, 2);

        // Heures normales (max 8h/jour)
        $this->regular_hours = min($this->total_hours, 8);

        // Heures supplémentaires
        $this->overtime_hours = max($this->total_hours - 8, 0);

        return $this;
    }

    public function checkLate()
    {
        // Heure d'arrivée normale : 7h30
        $normalStart = Carbon::parse($this->date)->setTime(7, 30);

        if ($this->clock_in) {
            $clockIn = Carbon::parse($this->clock_in);

            if ($clockIn->isAfter($normalStart)) {
                $this->is_late = true;
                $this->late_minutes = $normalStart->diffInMinutes($clockIn);
            } else {
                $this->is_late = false;
                $this->late_minutes = 0;
            }
        }

        return $this;
    }

    public function checkEarlyDeparture()
    {
        // Heure de départ normale : 15h30
        $normalEnd = Carbon::parse($this->date)->setTime(15, 30);

        if ($this->clock_out) {
            $clockOut = Carbon::parse($this->clock_out);

            if ($clockOut->isBefore($normalEnd)) {
                $this->is_early_departure = true;
                $this->early_departure_minutes = $clockOut->diffInMinutes($normalEnd);
            } else {
                $this->is_early_departure = false;
                $this->early_departure_minutes = 0;
            }
        }

        return $this;
    }

    public function validate($validatedBy = null)
    {
        $this->is_validated = true;
        $this->validated_by = $validatedBy ?? auth()->id();
        $this->validated_at = now();
        $this->save();

        return $this;
    }

    // Libellés
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'present' => 'Présent',
            'absent' => 'Absent',
            'late' => 'En retard',
            'half_day' => 'Demi-journée',
            'on_leave' => 'En congé',
            'on_mission' => 'En mission',
            'sick' => 'Malade',
            default => $this->status,
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'present' => 'success',
            'absent' => 'danger',
            'late' => 'warning',
            'half_day' => 'info',
            'on_leave' => 'primary',
            'on_mission' => 'secondary',
            'sick' => 'danger',
            default => 'gray',
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($attendance) {
            // Calculer automatiquement avant sauvegarde
            $attendance->calculateHours();
            $attendance->checkLate();
            $attendance->checkEarlyDeparture();
        });
    }
}
