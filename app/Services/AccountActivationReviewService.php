<?php

namespace App\Services;

use App\Models\AccountActivationRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Circuit de validation des demandes du Service RH. Deux démarches, mêmes règles
 * d'identité : ouvrir un compte que l'employé n'a pas pu activer seul (l'agent
 * applique le mot de passe que l'employé a choisi), ou réinitialiser un mot de
 * passe oublié (l'agent génère un mot de passe temporaire à remettre en main propre).
 */
class AccountActivationReviewService
{
    /**
     * @return string|null Le mot de passe temporaire généré, pour une réinitialisation
     *                     uniquement. Il n'est jamais stocké en clair : l'agent doit
     *                     le lire à la personne sur-le-champ.
     */
    public function validate(User $agent, AccountActivationRequest $request): ?string
    {
        if (!$request->isValidatable()) {
            throw ValidationException::withMessages([
                'request' => $request->isExpired()
                    ? 'Cette demande a expiré : l\'employé doit la relancer depuis son écran.'
                    : 'Cette demande n\'est plus en attente.',
            ]);
        }

        $user = $request->user;

        if (!$user) {
            throw ValidationException::withMessages([
                'request' => 'Le compte associé à cette demande n\'existe plus.',
            ]);
        }

        $temporaryPassword = null;

        if ($request->isPasswordReset()) {
            $temporaryPassword = $this->generateTemporaryPassword();

            $user->forceFill(['password' => Hash::make($temporaryPassword)])->save();
        } else {
            // Un compte déjà activé n'a rien à activer une seconde fois : le refus
            // serait une erreur de manipulation, pas une demande à traiter.
            if ($user->isActivated()) {
                throw ValidationException::withMessages([
                    'request' => 'Ce compte est déjà activé : rejetez la demande plutôt que de la valider.',
                ]);
            }

            $user->forceFill([
                'password' => $request->requested_password_hash,
                'activated_at' => now(),
            ])->save();
        }

        if ($user->roles()->count() === 0) {
            $user->assignRole('employee');
        }

        $request->forceFill([
            'status' => AccountActivationRequest::STATUS_VALIDATED,
            'validated_by' => $agent->id,
            'validated_at' => now(),
        ])->save();

        return $temporaryPassword;
    }

    /**
     * Nouveau mot de passe temporaire pour une réinitialisation déjà validée : le
     * précédent cesse de fonctionner aussitôt. Vérifié ici comme dans la politique,
     * car une action Filament masquée reste appelable par requête directe.
     */
    public function regenerateTemporaryPassword(AccountActivationRequest $request): string
    {
        if (!$request->isPasswordReset() || $request->status !== AccountActivationRequest::STATUS_VALIDATED) {
            throw ValidationException::withMessages([
                'request' => 'Aucun mot de passe à régénérer : cette demande n\'est pas une réinitialisation validée.',
            ]);
        }

        $user = $request->user;

        if (!$user) {
            throw ValidationException::withMessages([
                'request' => 'Le compte associé à cette demande n\'existe plus.',
            ]);
        }

        $temporaryPassword = $this->generateTemporaryPassword();

        $user->forceFill(['password' => Hash::make($temporaryPassword)])->save();

        return $temporaryPassword;
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

    /**
     * Mot de passe temporaire pensé pour être lu à voix haute : préfixe de
     * l'établissement + 6 caractères sans lettres ni chiffres ambigus (pas de O/0, I/1).
     */
    private function generateTemporaryPassword(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        do {
            $suffix = '';
            for ($i = 0; $i < 6; $i++) {
                $suffix .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (!preg_match('/[23456789]/', $suffix));

        return 'Hgy@' . $suffix;
    }
}
