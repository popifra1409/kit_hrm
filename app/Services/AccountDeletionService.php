<?php

namespace App\Services;

use App\Models\AccountDeletion;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccountDeletionService
{
    /**
     * Supprime le compte utilisateur d'un employé (l'employé reste en base avec
     * toutes ses informations — seul le compte de connexion disparaît).
     *
     * @throws \RuntimeException si la suppression casserait l'accès administrateur
     */
    public function delete(
        User $user,
        string $reason,
        ?string $notes,
        string $initiatedBy, // 'admin' | 'self'
        ?User $deletedBy = null,
    ): void {
        if ($this->isLastSuperAdmin($user)) {
            throw new \RuntimeException("Impossible de supprimer ce compte : c'est le dernier compte super_admin actif.");
        }

        if (!$user->employee_id) {
            throw new \RuntimeException("Ce compte n'est lié à aucune fiche employé — utilisez la suppression standard si nécessaire.");
        }

        DB::transaction(function () use ($user, $reason, $notes, $initiatedBy, $deletedBy) {
            AccountDeletion::create([
                'employee_id' => $user->employee_id,
                'matricule' => $user->employee?->matricule,
                'user_name' => $user->name,
                'user_email' => $user->email,
                'reason' => $reason,
                'notes' => $notes,
                'initiated_by' => $initiatedBy,
                'deleted_by' => $deletedBy?->id,
            ]);

            // Révoque tous les jetons d'API (Sanctum) avant suppression du compte.
            $user->tokens()->delete();

            $user->delete();
        });
    }

    protected function isLastSuperAdmin(User $user): bool
    {
        if (!$user->hasRole('super_admin')) {
            return false;
        }

        return User::role('super_admin')->count() <= 1;
    }
}
