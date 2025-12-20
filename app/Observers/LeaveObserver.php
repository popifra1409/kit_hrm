<?php

namespace App\Observers;

use App\Models\Leave;
use App\Services\NotificationService;

class LeaveObserver
{
    /**
     * Handle the Leave "updated" event.
     */
    public function updated(Leave $leave): void
    {
        // Vérifier si le statut a changé
        if ($leave->wasChanged('status')) {
            $this->sendStatusNotification($leave);
        }
    }

    /**
     * Envoyer une notification selon le statut
     */
    protected function sendStatusNotification(Leave $leave): void
    {
        if (!$leave->employee || !$leave->employee->user) {
            return;
        }

        $notificationService = new NotificationService();

        $templateCode = match ($leave->status) {
            'approved' => 'leave_approved',
            'rejected' => 'leave_rejected',
            default => null,
        };

        if (!$templateCode) {
            return;
        }

        $variables = [
            'employee_name' => $leave->employee->full_name,
            'leave_type' => $leave->leaveType?->name ?? 'Congé',
            'start_date' => $leave->start_date->format('d/m/Y'),
            'end_date' => $leave->end_date->format('d/m/Y'),
            'days' => $leave->days_requested,
            'approved_by' => $leave->approvedBy?->name ?? 'N/A',
            'rejection_reason' => $leave->rejection_reason ?? 'Non spécifié',
        ];

        try {
            $notificationService->send(
                $leave->employee->user,
                $templateCode,
                $variables,
                route('filament.admin.resources.leaves.view', $leave),
                'Voir le congé'
            );

            \Log::info("✅ Notification envoyée pour congé ID: {$leave->id}, Statut: {$leave->status}");
        } catch (\Exception $e) {
            \Log::error("❌ Erreur notification congé: " . $e->getMessage());
        }
    }
}
