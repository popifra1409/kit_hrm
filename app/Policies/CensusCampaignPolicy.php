<?php

namespace App\Policies;

use App\Models\CensusCampaign;
use App\Models\User;

class CensusCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('view_census_campaigns');
    }

    public function view(User $user, CensusCampaign $censusCampaign): bool
    {
        return $user->can('view_census_campaigns');
    }

    public function create(User $user): bool
    {
        return $user->can('create_census_campaigns');
    }

    public function update(User $user, CensusCampaign $censusCampaign): bool
    {
        return $user->can('edit_census_campaigns');
    }

    public function delete(User $user, CensusCampaign $censusCampaign): bool
    {
        return $user->can('delete_census_campaigns');
    }

    public function restore(User $user, CensusCampaign $censusCampaign): bool
    {
        return $user->can('edit_census_campaigns');
    }

    public function forceDelete(User $user, CensusCampaign $censusCampaign): bool
    {
        return $user->hasRole('super_admin') && $user->can('delete_census_campaigns');
    }
}
