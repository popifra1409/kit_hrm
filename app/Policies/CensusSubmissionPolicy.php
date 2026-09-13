<?php

namespace App\Policies;

use App\Models\CensusSubmission;
use App\Models\User;

class CensusSubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_census_submissions');
    }

    public function view(User $user, CensusSubmission $censusSubmission): bool
    {
        return $user->can('view_census_submissions');
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CensusSubmission $censusSubmission): bool
    {
        return $user->can('validate_census_submissions') || $user->can('reject_census_submissions');
    }

    public function delete(User $user, CensusSubmission $censusSubmission): bool
    {
        return $user->hasRole('super_admin');
    }
}
