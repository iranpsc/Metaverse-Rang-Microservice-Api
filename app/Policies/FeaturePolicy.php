<?php

namespace App\Policies;

use App\Models\Feature;
use App\Models\User;
use App\Helpers\FeatureIndicators;
use App\Models\BuyFeatureRequest;
use Illuminate\Auth\Access\HandlesAuthorization;
class FeaturePolicy
{
    use HandlesAuthorization;

    private $notAllowedToBeSoldFeatures = [];
    private $soldAndNotPriced = [];

    public function __construct()
    {
        $this->notAllowedToBeSoldFeatures = [
            FeatureIndicators::Edari,
            FeatureIndicators::Farhangi,
            FeatureIndicators::FazaSabz,
            FeatureIndicators::Parking,
            FeatureIndicators::Gardeshgari,
            FeatureIndicators::Nemayeshgah,
            FeatureIndicators::Behdashti,
        ];

        $this->soldAndNotPriced = [
            FeatureIndicators::MaskoniSoldAndNotPriced,
            FeatureIndicators::TejariSoldAndNotPriced,
            FeatureIndicators::AmozeshiSoldAndNotPriced,
            FeatureIndicators::MaskoniNotPriced,
            FeatureIndicators::TejariNotPriced,
            FeatureIndicators::AmozeshiNotPriced,
        ];
    }

    public function buy(User $user, Feature $feature)
    {
        return !in_array($feature->properties->karbari, $this->notAllowedToBeSoldFeatures)
            && !in_array($feature->properties->rgb, $this->soldAndNotPriced)
            && $feature->owner->isNot($user);
    }

    public function sell(User $user, Feature $feature)
    {
        $hasUnderEighteenPermissions = true;
        if ($user->isUnderEighteen()) {
            $hasUnderEighteenPermissions = $user->permissions?->verified && $user->permissions?->SF;
        }
        return $feature->owner->is($user)
            && $user->verified()
            && !$feature->hasPendingRequests()
            && !$feature->locked()
            && is_null($feature->dynasty)
            && $hasUnderEighteenPermissions;
    }

    public function sendBuyRequest(User $user, Feature $feature)
    {
        $rgb = User::firstWhere('code', 'hm-2000000');
        return BuyFeatureRequest::where('buyer_id', $user->id)
            ->where('feature_id', $feature->id)
            ->where('status', 0)
            ->doesntExist()
            && $feature->owner->isNot($rgb)
            && $feature->owner->isNot($user);
    }
}
