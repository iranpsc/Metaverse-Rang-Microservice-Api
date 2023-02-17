<?php

namespace App\Http\Controllers\Api\V1\Feature;

use App\Events\FeatureStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\SellFeatureRequestValidate;
use App\Http\Resources\SellRequestResource;
use App\Models\Feature;
use App\Models\SellFeatureRequest;
use App\Models\SystemVariable;
use App\Models\Variable;
use App\Notifications\SellRequestNotification;

class SellRequestsController extends Controller
{

    public function __construct()
    {
        $this->middleware(['account.security', 'verified'])->except(['index']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return SellRequestResource::collection(request()->user()->sellRequests);
    }

    public function store(SellFeatureRequestValidate $request, Feature $feature)
    {
        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit');
        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit');

        $requestedPrice_psc = $request->price_psc;
        $requestedPrice_irr = $request->price_irr;

        if ($request->has('minimum_price_percentage')) {
            if ($request->user()->isUnderEighteen() && $request->minimum_price_percentage < $under18PricingLimit) {
                abort(403, sprintf("شما مجاز به فروش زمین خود به کمتر از %s درصد قیمت خرید ملک نمی باشید", $under18PricingLimit));
            } elseif ($request->minimum_price_percentage < $publicPricingLimit) {
                abort(403, sprintf("شما مجاز به فروش زمین خود به کمتر از %s درصد قیمت خرید ملک نمی باشید", $publicPricingLimit));
            }

            $totalPrice = $feature->properties->stability * Variable::getRate($feature->getColor()) * $request->minimum_price_percentage / 100;
            $requestedPrice_psc = $totalPrice / Variable::getRate('psc') * 0.5;
            $requestedPrice_irr = $totalPrice * 0.5;
            $pricing_percentage = $request->minimum_price_percentage;
        } else {
            $totalRequested_price = $request->price_psc * Variable::getRate('psc') + $request->price_irr;
            $totalTradedPrice = $feature->properties->stability * Variable::getRate($feature->getColor());
            $pricing_percentage = intval($totalRequested_price / $totalTradedPrice * 100);

            if ($request->user()->isUnderEighteen() && $pricing_percentage < $under18PricingLimit) {
                abort(403, sprintf("شما مجاز به فروش زمین خود به کمتر از %s درصد قیمت خرید ملک نمی باشید", $under18PricingLimit));
            } elseif ($pricing_percentage < $publicPricingLimit) {
                abort(403, sprintf("شما مجاز به فروش زمین خود به کمتر از %s درصد قیمت خرید ملک نمی باشید", $publicPricingLimit));
            }
        }

        $sellRequest = SellFeatureRequest::create([
            'seller_id' => $feature->owner->id,
            'feature_id' => $feature->id,
            'price_psc' => $requestedPrice_psc,
            'price_irr' => $requestedPrice_irr,
            'limit'     => $pricing_percentage,
        ]);

        $feature->properties->update([
            'rgb' => $feature->changeStatusToSoldAndPriced(),
            'price_psc' => $sellRequest->price_psc,
            'price_irr' => $sellRequest->price_irr,
            'minimum_price_percentage' => $pricing_percentage
        ]);

        broadcast(new FeatureStatusChanged([
            'id'  => $feature->id,
            'rgb' => $feature->changeStatusToSoldAndPriced(),
        ]));

        $request->user()->notify(new SellRequestNotification($feature));

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
            'rgb' => $feature->changeStatusToSoldAndNotPriced()
        ]);
        $sellRequest->delete();
        broadcast(new FeatureStatusChanged([
            'id'  => $feature->id,
            'rgb' => $feature->changeStatusToSoldAndNotPriced()
        ]));
        return response()->noContent();
    }
}
