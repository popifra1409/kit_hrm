<?php

namespace App\Policies;

use App\Models\QuotpartPeriod;
use App\Models\User;

class QuotpartPeriodPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf', 'drh']);
    }

    public function view(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf', 'drh']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function update(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        // Ne peut pas modifier si clôturée
        if ($quotpartPeriod->status === 'closed') {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function delete(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function close(User $user, QuotpartPeriod $quotpartPeriod): bool
    {
        return $user->hasAnyRole(['super_admin', 'daf', 'dg']);
    }
}
