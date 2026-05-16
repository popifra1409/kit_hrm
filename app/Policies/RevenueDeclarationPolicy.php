<?php

namespace App\Policies;

use App\Models\RevenueDeclaration;
use App\Models\User;

class RevenueDeclarationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf', 'drh']);
    }

    public function view(User $user, RevenueDeclaration $revenueDeclaration): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf', 'drh']);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function update(User $user, RevenueDeclaration $revenueDeclaration): bool
    {
        // Ne peut pas modifier si validée
        if ($revenueDeclaration->status === 'validated') {
            return false;
        }

        return $user->hasAnyRole(['super_admin', 'admin', 'daf']);
    }

    public function delete(User $user, RevenueDeclaration $revenueDeclaration): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }

    public function validate(User $user, RevenueDeclaration $revenueDeclaration): bool
    {
        return $user->hasAnyRole(['super_admin', 'daf', 'dg']);
    }
}
