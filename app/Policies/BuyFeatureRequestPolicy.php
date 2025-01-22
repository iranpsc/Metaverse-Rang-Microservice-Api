<?php

namespace App\Policies;

use App\Models\BuyFeatureRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
class BuyFeatureRequestPolicy
{
    use HandlesAuthorization;

    public function delete(User $user, BuyFeatureRequest $buyFeatureRequest) {
        return $buyFeatureRequest->buyer->is($user);
    }

    public function reject(User $user, BuyFeatureRequest $buyFeatureRequest) {
        return $buyFeatureRequest->seller->is($user);
    }

    public function accept(User $user, BuyFeatureRequest $buyFeatureRequest) {
        return $buyFeatureRequest->seller->is($user) && $buyFeatureRequest->status === 0;
    }

    public function addGracePeriod(User $user, BuyFeatureRequest $buyFeatureRequest) {
        return $buyFeatureRequest->seller->is($user) && $buyFeatureRequest->status === 0;
    }
}
