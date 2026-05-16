<?php

namespace App\Policies;

use App\Models\QuotpartDistribution;
use App\Models\User;

class QuotpartDistributionPolicy
{
    /**
     * Voir la liste
     */
    public function viewAny(User $user): bool
    {
        return $user->can('distribute_quotparts');
    }

    /**
     * Voir un élément spécifique
     */
    public function view(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->can('distribute_quotparts');
    }

    /**
     * Créer
     */
    public function create(User $user): bool
    {
        return $user->can('distribute_quotparts');
    }

    /**
     * Modifier
     */
    public function update(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->can('distribute_quotparts');
    }

    /**
     * Supprimer
     */
    public function delete(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->can('distribute_quotparts');
    }

    /**
     * Restaurer
     */
    public function restore(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->can('distribute_quotparts');
    }

    /**
     * Supprimer définitivement
     */
    public function forceDelete(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->hasRole('super_admin') && $user->can('distribute_quotparts');
    }
}