<?php

namespace App\Policies;

use App\Models\Opportunity;
use App\Models\User;

class OpportunityPolicy
{
    public function save(User $user, Opportunity $opportunity): bool
    {
        return $opportunity->isVisibleToStudents();
    }

    public function unsave(User $user, Opportunity $opportunity): bool
    {
        return true;
    }
}
