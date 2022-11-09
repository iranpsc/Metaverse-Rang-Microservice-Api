<?php

namespace App\Http\Controllers\Feature;

use App\Events\FeatureTraded;
use App\Http\Controllers\Controller;
use App\Models\BuyFeatureRequest;
use App\Helpers\BuyFeatureRequestHelper;
use App\Http\Requests\BuyFeatureRequestValidate;
use App\Helpers\AssetHelper;
use App\Models\Feature;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Helpers\FeatureHelper;
use App\Http\Resources\BuyRequestResource;
use App\Models\Comission;
use App\Http\Resources\FeatureResource;
use App\Models\SellFeatureRequest;
use App\Models\User;
use App\Notifications\BuyRequestNotification;

class BuyRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return BuyRequestResource::collection(auth()->user()->buyRequests);
    }

    /**
     * @param Feature $feature
     * @return FeatureResource|JsonResponse
     */
    public function buy(Feature $feature): FeatureResource|JsonResponse
    {

        if ($feature->underPriced()) {
            // Get the latest under priced sell request for the owner of this feature
            $latestUnderPricedRequest = SellFeatureRequest::latestUnderPriceRequests($feature->owner, $feature)->last();
            if ($latestUnderPricedRequest) {
                $featureTrade = Trade::latestFeatureTrades($latestUnderPricedRequest->feature)->last();
                if ($featureTrade->created_at->addHours(24) > now()) {
                    if ($featureTrade->created_at->diffInHours(now()) < 1) {
                        $elapsedTime = $featureTrade->created_at->addDays(1)->diffInMinutes(now()) . ' دقیقه';
                    } else {
                        $elapsedTime = $featureTrade->created_at->addDays(1)->diffInHours(now()) . ' ساعت';
                    }
                    abort(403, 'این ملک زیر قیمت 100% قیمت گذاری شده است برای خرید آن بعد از ' . $elapsedTime . ' دوباره تلاش کنید');
                }
            }
        }

        $seller = $feature->owner;
        $buyer = request()->user();
        $error = AssetHelper::checkBalance($buyer, $feature);

        if (isset($error)) {
            return response()->json($error);
        }

        chargeBuyer($buyer, $feature);
        addSeller($seller, $feature);

        $trade = Trade::create([
            'feature_id' => $feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => $feature->properties->price_irr,
            'psc_amount' => $feature->properties->price_psc,
            'date' => now()
        ]);

        $rgb = User::firstWhere('code', 'hm-20000');

        $fees = fee($feature);

        $rgb->assets->increment('psc', $fees['psc'] * 2);
        $rgb->assets->increment('irr', $fees['irr'] * 2);

        Comission::create([
            'trade_id' => $trade->id,
            'psc' => $fees['psc'],
            'irr' => $fees['irr'],
        ]);

        $feature->update(['owner_id' => $buyer->id]);

        $feature->properties->update([
            'rgb' => FeatureHelper::getSoldAndNotPricedFeatureStatusColor($feature),
            'owner' => $buyer->name,
        ]);

        $feature->sellRequests->where('status', 0)
            ->where('seller_id', $seller->id)
            ->each->update(['status', 1]);

        FeatureHelper::cancelBuyRequests($feature);
        $feature->sellRequests->each->update([
            'status' => 1
        ]);

        $buyer->traded();
        $seller->traded();

        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();

        $seller->assets->increment($profit->asset, $profit->amount);

        $feature->hourlyProfit->update([
            'user_id' => $buyer->id,
            'amount' => 0,
            'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 3600),
        ]);

        $message = 'خرید با موفقیت انجام شد';
        $feature->message = $message;
        event(new FeatureTraded($trade));
        return new FeatureResource($feature);
    }


    /**
     * @param BuyFeatureRequestValidate $request
     * @param Feature $feature
     * @return JsonResponse|BuyRequestResource
     */
    public function store(BuyFeatureRequestValidate $request, Feature $feature): JsonResponse|BuyRequestResource
    {
        $buyer = request()->user();
        $seller = $feature->owner;
        $price_psc = $request->input('price_psc', 0);
        $price_irr = $request->input('price_irr', 0);

        $color = AssetHelper::getAssetColor($feature);
        $totalColorPrice = currentColorPrice($color) * $feature->properties->stability;

        $requestedTotalPrice = $price_irr + $price_psc * currentPscPrice();

        $priceDiffPercentage = ($requestedTotalPrice / $totalColorPrice) * 100;

        $featureSellRequest = SellFeatureRequest::where('seller_id', $seller->id)
            ->where('feature_id', $feature->id)
            ->where('status', 0)
            ->first();

        if ($featureSellRequest) {
            if ($priceDiffPercentage < $featureSellRequest->limit) {
                abort(403, 'شما مجاز به ارسال درخواست خرید به کمتر از کف قیمت تعیین شده نمی باشید');
            }
        }

        if ($priceDiffPercentage < $feature->properties->minimum_price_percentage) {
            abort(403, 'شما مجاز به ارسال درخواست خرید به کمتر از کف قیمت تعیین شده نمی باشید');
        }


        $error = BuyFeatureRequestHelper::checkErrors($buyer, $request, $feature);

        if (!empty($error)) {
            return response()->json(['error' => $error]);
        }

        $buyFeatureRequest = BuyFeatureRequest::create([
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'feature_id' => $feature->id,
            'note' => $request->input('note', ''),
            'price_psc' => $price_psc,
            'price_irr' => $price_irr,
        ]);

        AssetHelper::lockAsset($buyFeatureRequest, $request);

        $buyer->notify(new BuyRequestNotification($buyFeatureRequest));

        $message = 'درخواست خرید شما با موفقیت ثبت شد';

        $buyFeatureRequest->message = $message;
        return new BuyRequestResource($buyFeatureRequest);
    }

    public function recievedBuyRequests()
    {
        $requests = auth()->user()->recievedBuyRequests;
        return BuyRequestResource::collection($requests);
    }

    public function acceptBuyRequest(BuyFeatureRequest $buyFeatureRequest)
    {
        $feature = $buyFeatureRequest->feature;

        if ($feature->underPriced()) {
            // Get the latest under priced sell request for the owner of this feature
            $latestUnderPricedRequest = SellFeatureRequest::latestUnderPriceRequests($feature->owner, $feature)->last();
            if ($latestUnderPricedRequest) {
                $featureTrade = Trade::latestFeatureTrades($latestUnderPricedRequest->feature)->last();
                if ($featureTrade->created_at->addHours(24) > now()) {
                    if ($featureTrade->created_at->diffInHours(now()) < 1) {
                        $elapsedTime = $featureTrade->created_at->addDays(1)->diffInMinutes(now()) . ' دقیقه ';
                    } else {
                        $elapsedTime = $featureTrade->created_at->addDays(1)->diffInHours(now()) . ' ساعت ';
                    }
                    abort(403, 'شما در ۲۴ ساعت گذشته ملکی با زیر قیمت ۱۰۰٪ بفروش رسانده اید. برای پذیرش این درخواست باید ' . $elapsedTime . 'صبر کنید.');
                }
            }
        }

        if ($this->changeOwnerShip($buyFeatureRequest)) {
            $buyFeatureRequest->update(['status' => '1']);
            //Execute The Traded Event Observer
            $pscPrice = $buyFeatureRequest->price_psc * currentPscPrice();
            $irrPrice = $buyFeatureRequest->price_irr;

            if ($pscPrice + $irrPrice > 7000000) {
                $buyFeatureRequest->buyer->traded();
                $buyFeatureRequest->seller->traded();
            }

            $feature->sellRequests->each->update(['status' => 1]);

            return response()->json(['success' => 'معامله با موفقیت انجام شد']);
        }
    }

    private function changeOwnerShip(BuyFeatureRequest $buyFeatureRequest)
    {
        $feature = $buyFeatureRequest->feature;
        $property = $feature->properties;
        $buyer = $buyFeatureRequest->buyer;
        $seller = $buyFeatureRequest->seller;

        AssetHelper::releaseAsset($buyFeatureRequest);

        $feature->update(['owner_id' => $buyer->id]);
        $property->update([
            'rgb' => FeatureHelper::getSoldAndNotPricedFeatureStatusColor($feature),
            'owner' => $buyer->name,
        ]);

        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();

        $seller->assets->increment($profit->asset, $profit->amount);

        $feature->hourlyProfit->update([
            'user_id' => $buyer->id,
            'amount' => 0,
            'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 3600),
        ]);

        return true;
    }

    public function rejectBuyRequest(BuyFeatureRequest $buyFeatureRequest)
    {
        $buyFeatureRequest->update(['status' => '-1']);
        AssetHelper::releaseAsset($buyFeatureRequest, true);
        return response()->json(['error' => 'درخواست خرید رد شد']);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(BuyFeatureRequest $buyFeatureRequest): JsonResponse
    {
        AssetHelper::releaseAsset($buyFeatureRequest, true);
        $buyFeatureRequest->delete();
        return response()->json(['success' => 'درخواست خرید حذف شد!']);
    }
}
