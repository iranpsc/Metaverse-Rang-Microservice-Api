<?php

namespace App\Policies;

use App\Models\Feature;
use App\Models\User;
use App\Helpers\FeatureIndicators;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;
use App\Models\Dynasty\Dynasty;
use App\Models\Feature\FeaturePricingLimit;
use Illuminate\Http\Request;

class FeaturePolicy
{
    use HandlesAuthorization;

    public function buy(User $user, Feature $feature) {
        $notAllowedToSoldFeatures = [
            FeatureIndicators::Edari,
            FeatureIndicators::Farhangi,
            FeatureIndicators::FazaSabz,
            FeatureIndicators::Parking,
            FeatureIndicators::Gardeshgari,
            FeatureIndicators::Nemayeshgah,
            FeatureIndicators::Behdashti,
        ];

        $soldAndNotPriced = [
            FeatureIndicators::MaskoniSoldAndNotPriced,
            FeatureIndicators::TejariSoldAndNotPriced,
            FeatureIndicators::AmozeshiSoldAndNotPriced,
            FeatureIndicators::MaskoniNotPriced,
            FeatureIndicators::TejariNotPriced,
            FeatureIndicators::AmozeshiNotPriced,
        ];

        if(
            in_array($feature->properties->karbari, $notAllowedToSoldFeatures)
        ) {
            return Response::deny('این ملک قابل خرید و فروش نیست');
        } else if(
            in_array($feature->properties->rgb, $soldAndNotPriced)
            ) {
            return Response::deny('ملک مورد نظر به فروش گذاشته نشده است. شما می توانید پیشنهاد خرید برای این ملک ثبت کنید');
        } else if($user->id === $feature->owner_id){
            return Response::deny('این ملک متعلق به شما می باشد');
        }else {
           return Response::allow();
        }
    }

    public function sell(User $user, Feature $feature) {
        if($feature->hasPendingRequests())
        {
            return Response::deny('این ملک قبلا به فروش گذاشته شده است');
        }

        if( ! $user->ownField($feature))
        {
            return Response::deny('شما مالک این زمین نیستید');
        }

        if (! empty($feature->dynasty))
        {
            return Response::deny('ملکی که روی آن سلسله تاسیس شده است قابلیت خرید و فروش ندارد',403);
        }

        if(! $user->verified()) {
            return Response::deny('جهت فروش ملک خود باید احراز مرحله اول را انجام دهید', 403);
        }

        // if (isUnderEighteen($user)) {
        //     if(! $user->permissions->SF){
        //         abort(403, 'امکان فروش شما توسط پدر شما بسته شده است');
        //     }
        //     abort(403, 'شما جهت فروش ملک خود بایستی سلسله ای تاسیس کرده و پدر خود را معرفی کنید تا ایشان به شما این امکان را بدهد تا ملک خود را بفرویش برسانید');
        // }
        return true;
    }

    public function owned(User $user, Feature $feature) {
        return $user->id === $feature->owner_id
        ? Response::deny('شما مالک این زمین هستید')
        : Response::allow();
    }

    public function sendBuyRequest(User $user, Feature $feature)
    {
        return $feature->owner->name === 'rgb'
               || $user->ownField($feature)
               ? Response::deny('شما مجاز به ارسال درخواست خرید به این ملک نمی باشید')
               : Response::allow();
    }

    public function createDynasty(User $user, Feature $feature) {
       if($feature->properties->karbari != "m") return false;
       if(! $user->ownField($feature)) return false;
       return true;
    }
}
