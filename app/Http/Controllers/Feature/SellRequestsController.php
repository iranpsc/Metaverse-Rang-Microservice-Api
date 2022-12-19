<?php

namespace App\Http\Controllers\Feature;

use App\Events\FeatureStatusChanged;
use App\Helpers\AssetHelper;
use App\Http\Controllers\Controller;
use App\Helpers\FeatureHelper;
use App\Http\Requests\SellFeatureRequestValidate;
use App\Http\Resources\SellRequestResource;
use App\Models\Feature;
use App\Models\Feature\FeaturePricingLimit;
use App\Models\SellFeatureRequest;
use App\Notifications\SellRequestNotification;
use Illuminate\Validation\ValidationException;

class SellRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(count(request()->user()->sellRequests) === 0)
        {
            return response()->json([
                'error' => 'درخواست فروشی ثبت نشده است'
            ]);
        }
        return SellRequestResource::collection(request()->user()->sellRequests);
    }

    public function store(SellFeatureRequestValidate $request, Feature $feature)
    {
        $pricingLimit = FeaturePricingLimit::first();

        if (isset($request->minimum_price_percentage) && ($request->price_psc  || $request->price_irr)) {
            abort(403, 'قیمت ملک خود را یا با درصد یا با قیمت psc و یا ریال مشخص کنید');
        }

        if (isset($request->minimum_price_percentage)) {
            if(isUnderEighteen($request->user())) {
                if ($request->minimum_price_percentage < $pricingLimit->under_eighteen_price_limit) {
                    abort(403, "شما مجاز به فروش زمین خود به کمتر از {$pricingLimit->under_eighteen_price_limit} درصد قیمت خرید ملک نمی باشید");
                }
            }
            if ($request->minimum_price_percentage < $pricingLimit->public_price_limit) {
                abort(403, "شما مجاز به فروش زمین خود به کمتر از {$pricingLimit->public_price_limit}درصد قیمت خرید ملک نمی باشید");
            }
            $color = AssetHelper::getAssetColor($feature);
            $totalPrice = $feature->properties->stability * currentColorPrice($color) * ($request->minimum_price_percentage / 100);
            $requestedPrice_psc = ($totalPrice * 0.5) / currentPscPrice();
            $requestedPrice_irr = $totalPrice * 0.5;
            $price_limit = $request->minimum_price_percentage;
        } else {
            if (iszero($request->price_psc) && iszero($request->pirce_irr)) {
                throw ValidationException::withMessages([
                    'error' => 'قیمت ملک را یا به ریال یا به psc مشخص کنید'
                ]);
            }
            $price_limit = 0;

            $requestedPrice_psc = $request->price_psc;
            $requestedPrice_irr = $request->price_irr;

            $totalRequested_price = ($requestedPrice_psc * currentPscPrice()) + $requestedPrice_irr;
            $latestTraded = $feature->latestTraded;

            //If user bought this feature from RGB
            if ($latestTraded->seller->code == 'hm-2000000') {
                $tradedColor = AssetHelper::getAssetColor($feature);
                $totalTradedPrice = $feature->properties->stability * currentColorPrice($tradedColor);

                $price_limit = floor(($totalRequested_price / $totalTradedPrice) * 100);

                if(isUnderEighteen($request->user())) {
                    if ($request->minimum_price_percentage < $pricingLimit->under_eighteen_price_limit) {
                        abort(403, "شما مجاز به فروش زمین خود به کمتر از {$pricingLimit->under_eighteen_price_limit}درصد قیمت خرید ملک نمی باشید");
                    }
                }

                if ($price_limit < $pricingLimit->public_price_limit) {
                    abort(403, "شما مجاز به فروش زمین خود به کمتر از {$pricingLimit->public_price_limit} قیمت خرید ملک نمی باشید");
                }
            } else {
                //If user bought this feature from a User
                $totalTradedPrice = (currentPscPrice() * $latestTraded->psc_amount) + $latestTraded->irr_amount;
                $price_limit = floor(($totalRequested_price / $totalTradedPrice) * 100);
                // Check if the price limit is less than the allowed price limit by administor
                if(isUnderEighteen($request->user())) {
                    if ($request->minimum_price_percentage < $pricingLimit->under_eighteen_price_limit) {
                        abort(403, "شما مجاز به فروش زمین خود به کمتر از {$pricingLimit->under_eighteen_price_limit}درصد قیمت خرید ملک نمی باشید");
                    }
                }
                if ($price_limit < $pricingLimit->public_price_limit) {
                    abort(403, "شما مجاز به فروش زمین خود به کمتر از {$pricingLimit->public_price_limit} قیمت خرید ملک نمی باشید");
                }
            }
        }

        $sellRequest = SellFeatureRequest::create([
            'seller_id' => $feature->owner->id,
            'feature_id' => $feature->id,
            'status' => 0,
            'price_psc' => $requestedPrice_psc,
            'price_irr' => $requestedPrice_irr,
            'limit'     => $price_limit,
        ]);

        $feature->properties->update([
            'rgb' => FeatureHelper::changeStatus($feature),
            'price_psc' => $sellRequest->price_psc,
            'price_irr' => $sellRequest->price_irr,
            'minimum_price_percentage' => $price_limit
        ]);

        broadcast(new FeatureStatusChanged([
            'id' => $feature->properties->id,
            'rgb' => $feature->properties->rgb,
        ]));

        $request->user()->notify(new SellRequestNotification($feature));

        $sellRequest->message = 'ملک مورد نظر با موفقیت به فروش گذاشته شد';
        return new SellRequestResource($sellRequest);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(SellFeatureRequest $sellRequest)
    {
        $feature = $sellRequest->feature;

        $feature->properties->update([
            'rgb' => FeatureHelper::cancelSellRequest($feature)
        ]);

        $sellRequest->delete();
        broadcast(new FeatureStatusChanged([
            'id' => $feature->properties->id,
            'rgb' => FeatureHelper::cancelSellRequest($feature)
        ]));

        return response()->json(['success' => 'قیمت گذاری لغو شد']);
    }
}
