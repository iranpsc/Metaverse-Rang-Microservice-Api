<?php

namespace App\Policies;

use App\Models\Feature;
use App\Models\User;
use App\Helpers\FeatureIndicators;
use App\Models\BuyFeatureRequest;
use App\Models\Feature\BuildingModel;
use App\Models\Feature\FeatureLimit;
use App\Models\Image;
use App\Models\LimitedFeaturePurchase;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class FeaturePolicy
{
    use HandlesAuthorization;

    private $notAllowedToBeSoldFeatures = [];
    private $soldAndNotPriced = [];
    private $limitedFeatures = [];
    private $sellLimitedFeatures = [];

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

        $this->sellLimitedFeatures = [
            FeatureIndicators::MaskoniNotAllowedToBeSold,
            FeatureIndicators::TejariNotAllowedToBeSold,
            FeatureIndicators::AmoozeshiNotAllowedToBeSold,
        ];

        $this->soldAndNotPriced = [
            FeatureIndicators::MaskoniSoldAndNotPriced,
            FeatureIndicators::TejariSoldAndNotPriced,
            FeatureIndicators::AmozeshiSoldAndNotPriced,
            FeatureIndicators::MaskoniNotPriced,
            FeatureIndicators::TejariNotPriced,
            FeatureIndicators::AmozeshiNotPriced,
        ];

        $this->limitedFeatures = [
            FeatureIndicators::MaskoniTradingLimited,
            FeatureIndicators::TejariTradingLimited,
            FeatureIndicators::AmoozeshiTradingLimited,
        ];
    }

    /**
     * Determines if a user can buy a feature.
     *
     * @param User $user The user attempting to buy the feature.
     * @param Feature $feature The feature being bought.
     * @return bool|Response Returns true if the user can buy the feature, false otherwise.
     */
    public function buy(User $user, Feature $feature): Response|bool
    {
        $properties = $feature->properties;

        if (in_array($properties->rgb, $this->limitedFeatures)) {

            $featureLimitation = $this->getLimitation($feature);

            if ($featureLimitation) {
                return $this->handleLimitedFeature($user, $featureLimitation);
            }
        }

        return !in_array($properties->rgb, $this->sellLimitedFeatures)
            && !in_array($properties->karbari, $this->notAllowedToBeSoldFeatures)
            && !in_array($properties->rgb, $this->soldAndNotPriced)
            && $feature->owner->isNot($user)
            && !$feature->locked();
    }

    /**
     * Handles the limitations of a feature.
     *
     * @param User $user The user attempting to buy the feature.
     * @param FeatureLimit $featureLimitation The limitation of the feature.
     * @param object $properties The properties of the feature.
     * @return Response Returns a response if the user cannot buy the feature.
     */
    private function handleLimitedFeature(User $user, FeatureLimit $featureLimitation)
    {
        if ($featureLimitation->verified_kyc_limit && !$user->verified()) {
            return Response::deny(
                'جهت خرید ملک از طرح ' . $featureLimitation->title . ' باید احراز هویت خود را کامل کرده باشید.'
            );
        } elseif ($featureLimitation->under_18_limit && !$user->isUnderEighteen()) {
            return Response::deny(
                'جهت خرید ملک از طرح ' . $featureLimitation->title . ' باید زیر 18 سال سن داشته باشید.'
            );
        } elseif ($featureLimitation->more_than_18_limit && $user->isUnderEighteen()) {
            return Response::deny(
                'جهت خرید ملک از طرح ' . $featureLimitation->title . ' باید بالای 18 سال سن داشته باشید.'
            );
        } elseif ($featureLimitation->dynasty_owner_limit && is_null($user->dynasty)) {
            return Response::deny(
                'جهت خرید ملک از طرح ' . $featureLimitation->title . ' باید سلسله خود را تاسیس کرده باشید.'
            );
        } elseif ($featureLimitation->individual_buy_limit) {
            $limitedFeaturePurchuseCount = LimitedFeaturePurchase::where('user_id', $user->id)
                ->where('feature_limit_id', $featureLimitation->id)
                ->count();

            if ($limitedFeaturePurchuseCount >= $featureLimitation->individual_buy_count) {
                return Response::deny(
                    'شما قبلا از طرح ' . $featureLimitation->title . ' ' . $featureLimitation->individual_buy_count . ' ملک خریداری کرده اید.'
                );
            }
        } else {
            return Response::deny('خطایی رخ داده است لطفا با پشتیبانی تماس بگیرید.');
        }
    }

    /**
     * Gets the limitation of a feature.
     *
     * @param Feature $feature The feature for which the limitation is being retrieved.
     * @return FeatureLimit|null Returns the limitation of the feature if it exists, null otherwise.
     */
    private function getLimitation(Feature $feature): FeatureLimit|null
    {
        $properties = $feature->properties;

        return FeatureLimit::where('expired', false)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('start_id', '<=', $properties->id)
            ->where('end_id', '>=', $properties->id)
            ->first();
    }

    /**
     * Determines if a user can sell a feature.
     *
     * @param User $user The user attempting to sell the feature.
     * @param Feature $feature The feature being sold.
     * @return bool Returns true if the user can sell the feature, false otherwise.
     */
    public function sell(User $user, Feature $feature)
    {
        $hasUnderEighteenPermissions = true;

        if ($user->isUnderEighteen()) {
            $hasUnderEighteenPermissions = $user->permissions
                ? $user->permissions?->verified && $user->permissions?->SF
                : true;
        }

        return $feature->owner->is($user)
            && !in_array($feature->properties->rgb, $this->sellLimitedFeatures)
            && $user->verified()
            && !$feature->hasPendingRequests()
            && !$feature->locked()
            && is_null($feature->dynasty)
            && $hasUnderEighteenPermissions;
    }

    /**
     * Determines if a user can send a buy request for a feature.
     *
     * @param User $user The user sending the buy request.
     * @param Feature $feature The feature for which the buy request is being sent.
     * @return bool Returns true if the user can send the buy request, false otherwise.
     */
    public function sendBuyRequest(User $user, Feature $feature)
    {
        $rgb = User::firstWhere('code', 'hm-2000000');
        return BuyFeatureRequest::where('buyer_id', $user->id)
            ->where('feature_id', $feature->id)
            ->where('status', 0)
            ->doesntExist()
            && $feature->owner->isNot($rgb)
            && $feature->owner->isNot($user)
            && is_null($feature->dynasty);
    }

    /**
     * Determines if a user can update a feature.
     *
     * @param User $user The user attempting to update the feature.
     * @param Feature $feature The feature being updated.
     * @return bool Returns true if the user can update the feature, false otherwise.
     */
    public function update(User $user, Feature $feature)
    {
        return $feature->owner->is($user);
    }

    /**
     * Determines if a user can add an image to a feature.
     *
     * @param User $user The user attempting to add an image.
     * @param Feature $feature The feature to which the image is being added.
     * @return bool Returns true if the user can add an image, false otherwise.
     */
    public function addImage(User $user, Feature $feature)
    {
        return $feature->owner->is($user);
    }

    /**
     * Determines if a user can remove an image from a feature.
     *
     * @param User $user The user attempting to remove an image.
     * @param Feature $feature The feature from which the image is being removed.
     * @param Image $image The image being removed.
     * @return bool Returns true if the user can remove the image, false otherwise.
     */
    public function removeImage(User $user, Feature $feature, Image $image)
    {
        return $feature->owner->is($user) && $image->imageable->is($feature);
    }

    /**
     * Determines if a user can build a feature.
     *
     * @param User $user The user attempting to build the feature.
     * @param Feature $feature The feature being built.
     * @param BuildingModel $buildingModel The building model used for building.
     * @return bool Returns true if the user can build the feature, false otherwise.
     */
    public function build(User $user, Feature $feature, BuildingModel $buildingModel)
    {
        return $user->wallet->satisfaction >= $buildingModel->required_satisfaction
            && $feature->owner->is($user);
    }

    public function destroyBuilding(User $user, Feature $feature, BuildingModel $buildingModel)
    {
        return $feature->owner->is($user) && $buildingModel->building->is($feature);
    }
}
