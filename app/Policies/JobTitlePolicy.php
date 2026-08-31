<?php

namespace App\Policies;

use App\Models\JobTitle;
use App\Models\User;

class JobTitlePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_job_titles');
    }

    public function view(User $user, JobTitle $jobTitle): bool
    {
        return $user->can('view_job_titles');
    }

    public function create(User $user): bool
    {
        return $user->can('create_job_titles');
    }

    public function update(User $user, JobTitle $jobTitle): bool
    {
        return $user->can('edit_job_titles');
    }

    public function delete(User $user, JobTitle $jobTitle): bool
    {
        return $user->can('delete_job_titles');
    }

    public function forceDelete(User $user, JobTitle $jobTitle): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_job_titles');
    }

    public function restore(User $user, JobTitle $jobTitle): bool
    {
        return $user->can('edit_job_titles');
    }
}
