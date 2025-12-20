<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeAdvancementHistory extends Model
{
    use HasFactory;

    protected $table = 'employee_advancement_history';

    protected $fillable = [
        'employee_id',
        'advancement_type',
        'old_category',
        'old_echelon',
        'old_grade',
        'old_salary',
        'new_category',
        'new_echelon',
        'new_grade',
        'new_salary',
        'effective_date',
        'is_automatic',
        'reason',
        'decision_number',
        'decision_date',
        'decision_document_path',
        'approved_by',
        'notes',
    ];

    protected $casts = [
        'effective_date' => 'date',
        'decision_date' => 'date',
        'is_automatic' => 'boolean',
        'old_category' => 'integer',
        'old_echelon' => 'integer',
        'new_category' => 'integer',
        'new_echelon' => 'integer',
        'old_salary' => 'decimal:2',
        'new_salary' => 'decimal:2',
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

    // Méthode pour enregistrer automatiquement un avancement
    public static function recordAdvancement(
        Employee $employee,
        string $type,
        array $oldValues,
        array $newValues,
        array $details = []
    ) {
        return self::create([
            'employee_id' => $employee->id,
            'advancement_type' => $type,
            'old_category' => $oldValues['category'] ?? null,
            'old_echelon' => $oldValues['echelon'] ?? null,
            'old_grade' => $oldValues['grade'] ?? null,
            'old_salary' => $oldValues['salary'] ?? null,
            'new_category' => $newValues['category'] ?? null,
            'new_echelon' => $newValues['echelon'] ?? null,
            'new_grade' => $newValues['grade'] ?? null,
            'new_salary' => $newValues['salary'] ?? null,
            'effective_date' => $details['effective_date'] ?? now(),
            'is_automatic' => $details['is_automatic'] ?? false,
            'reason' => $details['reason'] ?? null,
            'decision_number' => $details['decision_number'] ?? null,
            'decision_date' => $details['decision_date'] ?? null,
            'decision_document_path' => $details['decision_document_path'] ?? null,
            'approved_by' => $details['approved_by'] ?? auth()->id(),
            'notes' => $details['notes'] ?? null,
        ]);
    }

    // Obtenir le libellé du type
    public function getTypeLabel(): string
    {
        return match($this->advancement_type) {
            'echelon' => 'Avancement d\'Échelon',
            'category' => 'Changement de Catégorie',
            'grade' => 'Changement de Grade',
            'salary_adjustment' => 'Ajustement Salarial',
            default => $this->advancement_type,
        };
    }

    // URL du document
    public function getDecisionDocumentUrlAttribute()
    {
        if ($this->decision_document_path) {
            return \Storage::url($this->decision_document_path);
        }
        return null;
    }
}