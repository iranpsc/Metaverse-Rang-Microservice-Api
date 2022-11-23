<?php

namespace App\Helpers;

use App\Models\BuyFeatureRequest;
use App\Models\Comission;
use App\Models\Feature;
use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Trade;

class AssetHelper
{

    public static function checkColorBalance(User $user, Feature $feature)
    {
        switch ($feature->properties->karbari) {
            case FeatureIndicators::Tejari:
                return $user->assets->red < $feature->properties->stability;
                break;
            case FeatureIndicators::Maskoni:
                return $user->assets->yellow < $feature->properties->stability;
                break;
            case FeatureIndicators::Amozeshi:
                return $user->assets->blue < $feature->properties->stability;
                break;
        }
    }

    public static function getAssetColor(Feature $feature)
    {
        switch ($feature->properties->karbari) {
            case FeatureIndicators::Amozeshi:
                return 'blue';
                break;
            case FeatureIndicators::Tejari:
                return 'red';
                break;
            case FeatureIndicators::Maskoni:
                return 'yellow';
                break;
        }
    }

    public static function lockAsset(BuyFeatureRequest $buyFeatureRequest, Request $request)
    {
        $buyer = $buyFeatureRequest->buyer;
        $psc_amount = $request->price_psc + ($request->price_psc * config('rgb.fee'));
        $irr_amount = $request->price_irr + ($request->price_irr * config('rgb.fee'));

        $buyer->assets->decrement('psc', $psc_amount);
        $buyer->assets->decrement('irr', $irr_amount);

        $buyer->lockedAssets()->create([
            'buy_feature_request_id' => $buyFeatureRequest->id,
            'feature_id' => $buyFeatureRequest->feature->id,
            'psc' => $psc_amount,
            'irr' => $irr_amount
        ]);
    }

    public static function releaseAsset(BuyFeatureRequest $buyFeatureRequest, $rejectOrCancel = false)
    {
        $psc_amount = $buyFeatureRequest->price_psc;
        $irr_amount = $buyFeatureRequest->price_irr;
        $buyer = $buyFeatureRequest->buyer;
        $seller = $buyFeatureRequest->seller;

        if ($rejectOrCancel) {
            $buyer->assets->increment('psc', $psc_amount);
            $buyer->assets->increment('irr', $irr_amount);
        } else {
            $seller->assets->increment('psc', ($psc_amount - ($psc_amount * config('rgb.fee'))));
            $seller->assets->increment('irr', ($irr_amount - ($irr_amount * config('rgb.fee'))));
            $rgb = User::firstWhere('code', 'hm-2000000');
            $psc_total_fee = $psc_amount * config('rgb.fee') * 2;
            $irr_total_fee = $irr_amount * config('rgb.fee') * 2;
            $rgb->assets->increment('psc', $psc_total_fee);
            $rgb->assets->increment('irr', $irr_total_fee);

            $trade = Trade::create([
                'feature_id' => $buyFeatureRequest->feature->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'irr_amount' => $irr_amount,
                'psc_amount' => $psc_amount,
                'date' => now()
            ]);

            Comission::create([
                'trade_id' => $trade->id,
                'psc' => $psc_total_fee,
                'irr' => $irr_total_fee,
            ]);
            self::cancelOthereRequests($buyFeatureRequest);
        }
        $buyFeatureRequest->lockedAsset->delete();
        $buyFeatureRequest->delete();
    }
    private static function cancelOthereRequests(BuyFeatureRequest $buyFeatureRequest)
    {
        $feature = $buyFeatureRequest->feature;

        foreach ($feature->buyRequests as $buyRequest) {
            if ($buyRequest->id == $buyFeatureRequest->id) continue;
            $price_psc = $buyRequest->lockedAsset->psc;
            $price_irr = $buyRequest->lockedAsset->irr;
            $buyRequest->buyer->assets->increment('psc', $price_psc);
            $buyRequest->buyer->assets->increment('irr', $price_irr);
            $buyRequest->lockedAsset->delete();
            $buyRequest->delete();
        }
    }

    public static function checkBalance(User $buyer, Feature $feature)
    {
        $totalPrice = totalPrice($feature, 'buyer', fee($feature));

        if (
            !iszero($feature->properties->price_psc)
            && !iszero($feature->properties->price_irr)
        ) {
            if ($buyer->assets->psc < $totalPrice['psc']) {
                return ['error' => 'موجودی psc شما کافی نمی باشد'];
            }

            if ($buyer->assets->irr < $totalPrice['irr']) {
                return ['error' => 'موجودی ریال شما کافی نمی باشد'];
            }
        }

        if (!iszero($feature->properties->price_psc)) {
            if ($buyer->assets->psc < $totalPrice['psc']) {
                return ['error' => 'موجودی psc شما کافی نمی باشد'];
            }
        }

        if (!iszero($feature->properties->price_irr)) {
            if ($buyer->assets->irr < $totalPrice['irr']) {
                return ['error' => 'موجودی ریال شما کافی نمی باشد'];
            }
        }

        return null;
    }

    public static function checkErrors(User $buyer, Request $request, Feature $feature)
    {
        $totalRequestedPrice = [
            'psc' => $request->price_psc + $request->price_psc * config('rgb.fee'),
            'irr' => $request->price_irr + $request->price_irr * config('rgb.fee'),
        ];

        if (!iszero($request->price_psc) && !iszero($request->price_irr)) {
            if ($request->price_psc < $feature->properties->price_psc) {
                return 'مقدار psc پیشنهادی شما کمتر از کف قیمت تعیین شده است';
            } else if ($totalRequestedPrice['psc'] > $buyer->assets->psc) {
                return 'موجودی psc شما کافی نمی باشد.';
            }
            if ($request->price_irr < $feature->properties->price_irr) {
                return 'مقدار ریال پیشنهادی شما کمتر از کف قیمت تعیین شده است';
            } else if ($totalRequestedPrice['irr'] > $buyer->assets->irr) {
                return 'موجودی ریال شما کافی نمی باشد.';
            }
        } else if (!iszero($request->price_psc)) {
            if ($totalRequestedPrice['psc'] < ($feature->properties->price_psc + $feature->properties->price_irr / currentPscPrice())) {
                return 'مقدار psc پیشنهادی شما کمتر از کف قیمت تعیین شده است';
            } else if ($totalRequestedPrice['psc'] > $buyer->assets->psc) {
                return 'موجودی psc شما کافی نمی باشد.';
            }
        } else if (!iszero($request->price_irr)) {
            if ($totalRequestedPrice['irr'] < ($feature->properties->price_irr + $feature->properties->price_psc * currentPscPrice())) {
                return 'مقدار ریال پیشنهادی شما کمتر از کف قیمت تعیین شده است';
            } else if ($totalRequestedPrice['irr'] > $buyer->assets->irr) {
                return 'موجودی ریال شما کافی نمی باشد.';
            }
        }
        return null;
    }
}
