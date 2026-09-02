<?php

namespace App\Observers;

use App\Models\EmployeeAffectation;
use App\Models\EmployeeAssignmentHistory;

class EmployeeAffectationObserver
{
    /**
     * Handle the EmployeeAffectation "created" event.
     */
    public function created(EmployeeAffectation $affectation): void
    {
        \Log::info('🔔 Observer EmployeeAffectation déclenché pour ID: ' . $affectation->id);

        $employee = $affectation->employee;

        if (!$employee) {
            \Log::error('❌ Employé introuvable pour affectation ID: ' . $affectation->id);
            return;
        }

        \Log::info('✅ Employé trouvé: ' . $employee->full_name);

        // Récupérer les anciennes valeurs
        $oldValues = $this->getOldValues($employee);

        // Récupérer le département depuis le service
        $department = $affectation->service?->department;

        // Créer automatiquement un enregistrement dans l'historique
        $history = EmployeeAssignmentHistory::create([
            'employee_id' => $affectation->employee_id,
            'assignment_type' => $this->determineType($affectation),

            // Anciennes valeurs
            'old_position_id' => $oldValues['position_id'],
            'old_position_title' => $oldValues['position_title'],
            'old_department_id' => $oldValues['department_id'],
            'old_department_name' => $oldValues['department_name'],
            'old_service_id' => $oldValues['service_id'],
            'old_service_name' => $oldValues['service_name'],

            // Nouvelles valeurs (depuis EmployeeAffectation)
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
            'decision_date' => null,
            'changed_by' => auth()->id(),
            'notes' => $affectation->notes,
        ]);

        \Log::info('✅ Historique créé avec ID: ' . $history->id);

        // Envoyer notification
        if ($employee->user) {
            $this->sendNotification($affectation, $oldValues);
        }
    }

    /**
     * Récupérer les anciennes valeurs de l'employé
     */
    protected function getOldValues($employee): array
    {
        return [
            'position_id' => $employee->qualification_id,
            'position_title' => $employee->qualification?->name,
            'department_id' => $employee->department_id,
            'department_name' => $employee->department?->name,
            'service_id' => $employee->service_id,
            'service_name' => $employee->service?->name,
        ];
    }

    /**
     * Déterminer le type d'affectation
     */
    protected function determineType(EmployeeAffectation $affectation): string
    {
        if ($affectation->qualification_id) {
            return 'position';
        }
        if ($affectation->service_id) {
            return 'service';
        }
        return 'service';
    }

    /**
     * Envoyer notification
     */
    protected function sendNotification(EmployeeAffectation $affectation, array $oldValues): void
    {
        try {
            $employee = $affectation->employee;
            $notificationService = new \App\Services\NotificationService();

            $type = $this->determineType($affectation);
            $changeType = match ($type) {
                'position' => 'poste',
                'service' => 'service',
                default => 'affectation',
            };

            $oldValue = match ($type) {
                'position' => $oldValues['position_title'] ?? 'N/A',
                'service' => $oldValues['service_name'] ?? 'N/A',
                default => 'N/A',
            };

            $newValue = match ($type) {
                'position' => $affectation->qualification?->name ?? 'N/A',
                'service' => $affectation->service?->name ?? 'N/A',
                default => 'N/A',
            };

            \Log::info('📧 Envoi notification à: ' . $employee->user->email);

            $notificationService->send(
                $employee->user,
                'assignment_change',
                [
                    'employee_name' => $employee->full_name,
                    'change_type' => $changeType,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                ],
                route('filament.admin.resources.employees.edit', $employee),
                'Voir les détails'
            );

            \Log::info('✅ Notification envoyée');
        } catch (\Exception $e) {
            \Log::error('❌ Erreur envoi notification affectation: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
        }
    }
}
