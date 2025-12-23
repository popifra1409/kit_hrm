<?php

namespace App\Filament\Resources\EmployeeAffectationResource\Pages;

use App\Filament\Resources\EmployeeAffectationResource;
use Filament\Resources\Pages\CreateRecord;
use App\Models\EmployeeAssignmentHistory;

class CreateEmployeeAffectation extends CreateRecord
{
    protected static string $resource = EmployeeAffectationResource::class;

    protected function afterCreate(): void
    {
        \Log::info('🔔 Hook afterCreate déclenché pour affectation ID: ' . $this->record->id);

        // Récupérer l'affectation créée
        $affectation = $this->record;

        // Charger les relations
        $affectation->load(['employee', 'service', 'position', 'service.department']);

        \Log::info('Service chargé: ' . ($affectation->service?->name ?? 'NULL'));
        \Log::info('Position chargée: ' . ($affectation->position?->name ?? 'NULL'));

        $employee = $affectation->employee;

        if (!$employee) {
            \Log::error('❌ Employé introuvable');
            return;
        }

        \Log::info('✅ Employé trouvé: ' . $employee->full_name);

        // Charger les relations de l'employé
        $employee->load(['service', 'position', 'department']);

        // Récupérer le département depuis le service
        $department = $affectation->service?->department;

        \Log::info('Département: ' . ($department?->name ?? 'NULL'));

        // Créer l'historique manuellement
        $history = EmployeeAssignmentHistory::create([
            'employee_id' => $affectation->employee_id,
            'assignment_type' => $affectation->position_id ? 'position' : 'service',

            // Anciennes valeurs (depuis l'employé)
            'old_position_id' => $employee->position_id,
            'old_position_name' => $employee->position?->name,
            'old_department_id' => $employee->department_id,
            'old_department_name' => $employee->department?->name,
            'old_service_id' => $employee->service_id,
            'old_service_name' => $employee->service?->name,

            // Nouvelles valeurs (depuis l'affectation)
            'new_position_id' => $affectation->position_id,
            'new_position_name' => $affectation->position?->name,
            'new_department_id' => $department?->id,
            'new_department_name' => $department?->name,
            'new_service_id' => $affectation->service_id,
            'new_service_name' => $affectation->service?->name,

            // Détails
            'effective_date' => $affectation->start_date ?? now(),
            'end_date' => $affectation->end_date,
            'is_temporary' => !($affectation->is_current ?? true),
            'reason' => $affectation->reason,
            'decision_number' => $affectation->decision_number,
            'changed_by' => auth()->id(),
            'notes' => $affectation->notes,
        ]);

        \Log::info('✅ Historique créé ID: ' . $history->id);
        \Log::info('New Service Name: ' . ($history->new_service_name ?? 'NULL'));
        \Log::info('New Position Name: ' . ($history->new_position_name ?? 'NULL'));

        \Filament\Notifications\Notification::make()
            ->title('Historique créé')
            ->success()
            ->body('L\'historique de l\'affectation a été enregistré.')
            ->send();
    }
}
