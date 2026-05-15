<?php

namespace App\Policies;

use App\Models\ContractType;
use App\Models\User;

class ContractTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_contracts');
    }

    public function view(User $user, ContractType $contractType): bool
    {
        return $user->can('view_contracts');
    }

    public function create(User $user): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'drh']);
    }

    public function update(User $user, ContractType $contractType): bool
    {
        return $user->hasRole(['super_admin', 'admin', 'drh']);
    }

    public function delete(User $user, ContractType $contractType): bool
    {
        return $user->hasRole(['super_admin', 'admin']);
    }
}
