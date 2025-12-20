<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAssignmentHistory extends Model
{
    use HasFactory;

    protected $table = 'employee_assignment_history';

    protected $fillable = [
        'employee_id',
        'assignment_type',
        'old_position_id',
        'old_position_title',
        'old_department_id',
        'old_department_name',
        'old_service_id',
        'old_service_name',
        'new_position_id',
        'new_position_title',
        'new_department_id',
        'new_department_name',
        'new_service_id',
        'new_service_name',
        'effective_date',
        'end_date',
        'is_temporary',
        'reason',
        'decision_number',
        'decision_date',
        'changed_by',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'end_date' => 'date',
        'decision_date' => 'date',
        'is_temporary' => 'boolean',
    ];

    // Relations
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function oldDepartment()
    {
        return $this->belongsTo(Department::class, 'old_department_id');
    }

    public function newDepartment()
    {
        return $this->belongsTo(Department::class, 'new_department_id');
    }

    public function oldService()
    {
        return $this->belongsTo(Service::class, 'old_service_id');
    }

    public function newService()
    {
        return $this->belongsTo(Service::class, 'new_service_id');
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    // Méthode pour créer automatiquement l'historique
    public static function recordChange(
        Employee $employee,
        string $type,
        array $oldValues,
        array $newValues,
        array $details = []
    ) {
        return self::create([
            'employee_id' => $employee->id,
            'assignment_type' => $type,
            'old_position_id' => $oldValues['position_id'] ?? null,
            'old_position_title' => $oldValues['position_title'] ?? null,
            'old_department_id' => $oldValues['department_id'] ?? null,
            'old_department_name' => $oldValues['department_name'] ?? null,
            'old_service_id' => $oldValues['service_id'] ?? null,
            'old_service_name' => $oldValues['service_name'] ?? null,
            'new_position_id' => $newValues['position_id'] ?? null,
            'new_position_title' => $newValues['position_title'] ?? null,
            'new_department_id' => $newValues['department_id'] ?? null,
            'new_department_name' => $newValues['department_name'] ?? null,
            'new_service_id' => $newValues['service_id'] ?? null,
            'new_service_name' => $newValues['service_name'] ?? null,
            'effective_date' => $details['effective_date'] ?? now(),
            'end_date' => $details['end_date'] ?? null,
            'is_temporary' => $details['is_temporary'] ?? false,
            'reason' => $details['reason'] ?? null,
            'decision_number' => $details['decision_number'] ?? null,
            'decision_date' => $details['decision_date'] ?? null,
            'changed_by' => auth()->id(),
            'notes' => $details['notes'] ?? null,
        ]);
    }

    // Obtenir le libellé du type
    public function getTypeLabel(): string
    {
        return match ($this->assignment_type) {
            'position' => 'Changement de Poste',
            'department' => 'Changement de Département',
            'service' => 'Changement de Service',
            'location' => 'Changement de Lieu',
            'contract_type' => 'Changement de Type de Contrat',
            default => $this->assignment_type,
        };
    }
}
