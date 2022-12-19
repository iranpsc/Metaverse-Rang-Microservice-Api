<?php

namespace App\Http\Controllers\Feature;

use App\Events\FeatureStatusChanged;
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
use App\Mail\BuyRequestRecievedMail;
use App\Models\SellFeatureRequest;
use App\Models\User;
use App\Notifications\BuyFeatureNotification;
use App\Notifications\BuyRequestNotification;
use App\Notifications\sellFeature;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class BuyRequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (count(auth()->user()->buyRequests) === 0) {
            return response()->json(['error' => 'درخواست خرید ثبت نشده است']);
        }
        return BuyRequestResource::collection(auth()->user()->buyRequests);
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

        if (iszero($request->price_irr) && iszero($request->price_psc)) {
            throw ValidationException::withMessages([
                'error' => 'قیمت قیمت پیشنهادی خود را یا به تومان یا به psc مشخص کنید'
            ]);
        }

        $error = AssetHelper::checkErrors($buyer, $request, $feature);

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

        $buyer->notify(new BuyRequestNotification([
            'id' => $feature->properties->id,
            'price_psc' => $buyFeatureRequest->price_psc,
            'price_irr' => $buyFeatureRequest->price_irr,
            'buyRequest' => $buyFeatureRequest,
        ]));

        Mail::to($seller)->send(new BuyRequestRecievedMail($buyFeatureRequest));

        $message = 'درخواست خرید شما با موفقیت ثبت شد';
        $buyFeatureRequest->message = $message;
        return new BuyRequestResource($buyFeatureRequest);
    }

    public function recievedBuyRequests()
    {
        $requests = request()->user()->recievedBuyRequests;
        if (count($requests) === 0) {
            return response()->json(['error' => 'درخواست خریدی دریافت نکرده اید.']);
        }
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
            $buyFeatureRequest->buyer->traded();
            $buyFeatureRequest->seller->traded();
            $feature->sellRequests->each->update(['status' => 1]);
            broadcast(new FeatureStatusChanged([
                'id' => $feature->properties->id,
                'rgb' => $feature->properties->rgb,

            ]));
            $buyFeatureRequest->buyer->notify(new BuyFeatureNotification([
                'feature' => $feature,
                'id' => $feature->properties->id,
                'buyer' => $buyFeatureRequest->buyer->name,
                'seller' => $buyFeatureRequest->seller->name,
                'template' => 'buy-land-user',
            ], $feature->latestTraded));
            $buyFeatureRequest->seller->notify(new sellFeature([
                'feature' => $feature,
                'id' => $feature->properties->id,
                'buyer' => $buyFeatureRequest->buyer->name,
                'seller' => $buyFeatureRequest->seller->name,
                'template' => 'buy-land-user',
            ], $feature->latestTraded));

            $feature->message = 'معامله با موفقیت انجام شد';
            return new FeatureResource($feature);
        }
    }

    private function changeOwnerShip(BuyFeatureRequest $buyFeatureRequest)
    {
        $feature = $buyFeatureRequest->feature;
        $properties = $feature->properties;
        $buyer = $buyFeatureRequest->buyer;
        $seller = $buyFeatureRequest->seller;

        AssetHelper::releaseAsset($buyFeatureRequest);

        $feature->update(['owner_id' => $buyer->id]);
        $properties->update([
            'rgb' => FeatureHelper::getSoldAndNotPricedFeatureStatusColor($feature),
            'owner' => $buyer->name,
            'price_psc' => $buyFeatureRequest->price_psc,
            'price_irr' => $buyFeatureRequest->price_irr,
        ]);

        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();

        $seller->assets->increment($profit->asset, $profit->amount);

        $feature->hourlyProfit->update([
            'user_id' => $buyer->id,
            'amount' => 0,
            'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 86400),
        ]);

        return true;
    }

    public function rejectBuyRequest(BuyFeatureRequest $buyFeatureRequest)
    {
        AssetHelper::releaseAsset($buyFeatureRequest, true);
        $buyFeatureRequest->delete();
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
