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

        // Charger les relations (position -> qualification)
        $affectation->load(['employee', 'service', 'qualification', 'service.department']);

        \Log::info('Service chargé: ' . ($affectation->service?->name ?? 'NULL'));
        \Log::info('Qualification chargée: ' . ($affectation->qualification?->name ?? 'NULL'));

        $employee = $affectation->employee;

        if (!$employee) {
            \Log::error('❌ Employé introuvable');
            return;
        }

        \Log::info('✅ Employé trouvé: ' . $employee->full_name);

        // Charger les relations de l'employé (position -> qualification/jobTitle)
        $employee->load(['currentService', 'qualification', 'jobTitle', 'department']);

        // Récupérer le département depuis le service
        $department = $affectation->service?->department;

        \Log::info('Département: ' . ($department?->name ?? 'NULL'));

        // Créer l'historique manuellement
        // Note : les colonnes de EmployeeAssignmentHistory s'appellent encore
        // old_position_id/old_position_title pour l'instant (non renommées),
        // mais elles stockent désormais des données de Qualification.
        $history = EmployeeAssignmentHistory::create([
            'employee_id' => $affectation->employee_id,
            'assignment_type' => $affectation->qualification_id ? 'position' : 'service',

            // Anciennes valeurs (depuis l'employé)
            'old_position_id' => $employee->qualification_id,
            'old_position_title' => $employee->qualification?->name,
            'old_department_id' => $employee->department_id,
            'old_department_name' => $employee->department?->name,
            'old_service_id' => $employee->current_service_id,
            'old_service_name' => $employee->currentService?->name,

            // Nouvelles valeurs (depuis l'affectation)
            'new_position_id' => $affectation->qualification_id,
            'new_position_title' => $affectation->qualification?->name,
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
        \Log::info('New Position Title: ' . ($history->new_position_title ?? 'NULL'));

        \Filament\Notifications\Notification::make()
            ->title('Historique créé')
            ->success()
            ->body('L\'historique de l\'affectation a été enregistré.')
            ->send();
    }
}
