<?php

namespace App\Policies;

use App\Models\QuotpartPeriod;
use App\Models\User;

class QuotpartPeriodPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_quotparts');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->can('view_quotparts');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('create_quotparts');
    }

    /**
     * Modifier
     */
    public function update(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->can('edit_quotparts');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->can('delete_quotparts');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->can('edit_quotparts');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_quotparts');
    }
}