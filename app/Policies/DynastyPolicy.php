<?php

namespace App\Policies;

use App\Models\Dynasty\Dynasty;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DynastyPolicy
{
    use HandlesAuthorization;

    public function create(User $user, Feature $feature)
    {
        return $user->verified()
            && is_null($user->dynasty)
            && $feature->properties->karbari === "m"
            && $feature->owner->is($user)
            && !$feature->hasPendingRequests();
    }

    public function updateDynastyFeature(User $user, Dynasty $dynasty, Feature $feature)
    {
        return !$feature->hasPendingRequests()
            && $feature->owner->is($user)
            && $dynasty->feature->isNot($feature)
            && $user->dynasty->is($dynasty);
    }
}
