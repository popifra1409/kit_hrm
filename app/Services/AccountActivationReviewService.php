<?php

namespace App\Services;

use App\Models\AccountActivationRequest;
use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * Circuit de validation des demandes d'activation : quand l'employé ne retrouve
 * pas sa date de recrutement, un agent des RH vérifie son identité au guichet
 * puis ouvre lui-même le compte avec le mot de passe que l'employé avait choisi.
 */
class AccountActivationReviewService
{
    public function validate(User $agent, AccountActivationRequest $request): void
    {
        if (!$request->isValidatable()) {
            throw ValidationException::withMessages([
                'request' => $request->isExpired()
                    ? 'Cette demande a expiré : l\'employé doit la relancer depuis son écran d\'activation.'
                    : 'Cette demande n\'est plus en attente.',
            ]);
        }

        $user = $request->user;

        if (!$user) {
            throw ValidationException::withMessages([
                'request' => 'Le compte associé à cette demande n\'existe plus.',
            ]);
        }

        if ($user->isActivated()) {
            throw ValidationException::withMessages([
                'request' => 'Ce compte est déjà activé : rejetez la demande plutôt que de la valider.',
            ]);
        }

        $user->forceFill([
            'password' => $request->requested_password_hash,
            'activated_at' => now(),
        ])->save();

        if ($user->roles()->count() === 0) {
            $user->assignRole('employee');
        }

        $request->forceFill([
            'status' => AccountActivationRequest::STATUS_VALIDATED,
            'validated_by' => $agent->id,
            'validated_at' => now(),
        ])->save();
    }

    public function reject(User $agent, AccountActivationRequest $request, string $reason): void
    {
        if (!$request->isPending()) {
            throw ValidationException::withMessages([
                'request' => 'Cette demande n\'est plus en attente.',
            ]);
        }

        $request->forceFill([
            'status' => AccountActivationRequest::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'validated_by' => $agent->id,
            'validated_at' => now(),
        ])->save();
    }
}
