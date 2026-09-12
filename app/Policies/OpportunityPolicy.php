<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function save(User $user, Opportunity $opportunity): bool
    {
        return ! $user->isOrganizationUser() && $opportunity->isVisibleToStudents();
    }

    public function unsave(User $user, Opportunity $opportunity): bool
    {
        return ! $user->isOrganizationUser();
    }

    public function viewOrganization(User $user, Opportunity $opportunity): bool
    {
        return $this->belongsToUserOrganization($user, $opportunity);
    }

    public function updateOrganization(User $user, Opportunity $opportunity): bool
    {
        if (! $user->isOrganizationOwner($opportunity->owningOrganization)) {
            return false;
        }

        if (! $this->belongsToUserOrganization($user, $opportunity)) {
            return false;
        }

        return $opportunity->isDraft() || $opportunity->isPending() || $opportunity->isRejected();
    }

    public function deleteOrganization(User $user, Opportunity $opportunity): bool
    {
        return $user->isOrganizationOwner($opportunity->owningOrganization)
            && $this->belongsToUserOrganization($user, $opportunity)
            && $opportunity->isDraft();
    }

    public function submitOrganization(User $user, Opportunity $opportunity): bool
    {
        return $user->isOrganizationOwner($opportunity->owningOrganization)
            && $this->belongsToUserOrganization($user, $opportunity)
            && ($opportunity->isDraft() || $opportunity->isRejected());
    }

    public function approve(User $user, Opportunity $opportunity): bool
    {
        return $user->isAdmin();
    }

    public function reject(User $user, Opportunity $opportunity): bool
    {
        return $user->isAdmin();
    }

    private function belongsToUserOrganization(User $user, Opportunity $opportunity): bool
    {
        $organization = $user->currentOrganization();

        return $organization !== null
            && $opportunity->organization_id !== null
            && (int) $opportunity->organization_id === (int) $organization->id;
    }
}
