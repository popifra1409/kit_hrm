<?php

namespace App\Policies;

use App\Models\AccountActivationRequest;
use App\Models\User;

class AccountActivationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_account_activation_requests');
    }

    public function view(User $user, AccountActivationRequest $accountActivationRequest): bool
    {
        return $user->can('view_account_activation_requests');
    }

    public function create(User $user): bool
    {
        // Une demande naît toujours de l'écran d'activation de l'employé.
        return false;
    }

    public function update(User $user, AccountActivationRequest $accountActivationRequest): bool
    {
        return false;
    }

    public function delete(User $user, AccountActivationRequest $accountActivationRequest): bool
    {
        return $user->hasRole('super_admin');
    }

    /**
     * Ouverture du compte : droits + demande encore validable. Vérifié ici et dans
     * le service, car une action Filament masquée reste appelable par requête directe.
     */
    public function validate(User $user, AccountActivationRequest $accountActivationRequest): bool
    {
        return $user->can('validate_account_activations') && $accountActivationRequest->isValidatable();
    }

    public function reject(User $user, AccountActivationRequest $accountActivationRequest): bool
    {
        return $user->can('validate_account_activations') && $accountActivationRequest->isPending();
    }
}
