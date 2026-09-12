<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        return $organization->isMember($user);
    }

    public function update(User $user, Organization $organization): bool
    {
        return $organization->isOwner($user);
    }

    public function manageMembers(User $user, Organization $organization): bool
    {
        return $organization->isOwner($user);
    }

    public function createOpportunity(User $user, Organization $organization): bool
    {
        return $organization->isOwner($user);
    }
}
