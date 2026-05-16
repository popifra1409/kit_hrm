<?php

namespace App\Policies;

use App\Models\QuotpartDistribution;
use App\Models\User;

class QuotpartDistributionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf', 'drh', 'dg']);
    }

    public function view(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf', 'drh', 'dg']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function update(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        // Ne peut pas modifier si approuvée
        if ($quotpartDistribution->status === 'approved') {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function delete(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function approve(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        return $user->hasAnyRole(['super_admin', 'dg', 'daf']);
    }

    public function distribute(User $user, QuotpartDistribution $quotpartDistribution): bool
    {
        // Seulement si approuvée
        if ($quotpartDistribution->status !== 'approved') {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'daf']);
    }
}
