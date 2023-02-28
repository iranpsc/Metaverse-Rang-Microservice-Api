<?php

namespace App\Http\Controllers\Api\V1\Feature;

use App\Events\FeatureStatusChanged;
use App\Http\Controllers\Controller;
use App\Models\BuyFeatureRequest;
use App\Http\Requests\BuyFeatureRequestValidate;
use App\Models\Feature;
use App\Models\Trade;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use App\Http\Resources\BuyRequestResource;
use App\Models\SellFeatureRequest;
use App\Models\Variable;
use App\Notifications\BuyFeatureNotification;
use App\Notifications\BuyRequestNotification;
use App\Notifications\sellFeature;
use App\Models\SystemVariable;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use App\Models\Comission;

class BuyRequestsController extends Controller
{

    public function __construct()
    {
        $this->middleware(['account.security', 'verified'])->except(['index', 'recievedBuyRequests']);
    }
    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        return BuyRequestResource::collection(request()->user()->buyRequests);
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
        $price_psc = $request->price_psc;
        $price_irr = $request->price_irr;

        $floor_price_percentage = $feature->properties->minimum_price_percentage;

        $totalRequestedPrice = $price_irr + $price_psc * Variable::getRate('psc');
        $totalFeaturePrice = $feature->properties->stability * Variable::getRate($feature->getColor());

        if ($totalRequestedPrice / $totalFeaturePrice * 100 < $floor_price_percentage) {
            abort(403, sprintf("شما به مجاز به ارسال درخواست خرید به کمتر از %s قیمت ملک نمی باشید!", $floor_price_percentage));
        }

        if ($buyer->assets->psc < $price_psc + $price_psc * config('rgb.fee')) {
            throw ValidationException::withMessages([
                'price_psc' => 'موجودی psc شما کافی نیست!'
            ]);
        } elseif ($buyer->assets->irr < $price_irr + $price_irr * config('rgb.fee')) {
            throw ValidationException::withMessages([
                'price_irr' => 'موجودی ریال شما کافی نیست!'
            ]);
        }

        $buyFeatureRequest = BuyFeatureRequest::create([
            'buyer_id'   => $buyer->id,
            'seller_id'  => $seller->id,
            'feature_id' => $feature->id,
            'note'       => $request->input('note', ''),
            'price_psc'  => $price_psc,
            'price_irr'  => $price_irr,
        ]);

        $price_psc = $price_psc + $price_psc * config('rgb.fee');
        $price_irr = $price_irr + $price_irr * config('rgb.fee');

        $buyer->assets->decrement('psc', $price_psc);
        $buyer->assets->decrement('irr', $price_irr);

        $buyer->lockedAssets()->create([
            'buy_feature_request_id' => $buyFeatureRequest->id,
            'feature_id'             => $buyFeatureRequest->feature->id,
            'psc'                    => $price_psc,
            'irr'                    => $price_irr
        ]);

        $buyFeatureRequest->transactions()->create([
            'user_id' => $buyer->id,
            'asset'   => 'psc',
            'amount'  => $price_psc,
            'action'  => 'withdraw',
        ]);

        $buyFeatureRequest->transactions()->create([
            'user_id' => $buyer->id,
            'asset'   => 'irr',
            'amount'  => $price_irr,
            'action'  => 'withdraw',
        ]);

        $buyer->notify(new BuyRequestNotification([
            'id'         => $feature->properties->id,
            'price_psc'  => $buyFeatureRequest->price_psc,
            'price_irr'  => $buyFeatureRequest->price_irr,
            'buyRequest' => $buyFeatureRequest,
            'type'       => 'buyer'
        ]));

        $seller->notify(new BuyRequestNotification([
            'id'         => $feature->properties->id,
            'price_psc'  => $buyFeatureRequest->price_psc,
            'price_irr'  => $buyFeatureRequest->price_irr,
            'buyRequest' => $buyFeatureRequest,
            'type'       => 'seller'
        ]));

        return new BuyRequestResource($buyFeatureRequest);
    }

    public function recievedBuyRequests()
    {
        return BuyRequestResource::collection(request()->user()->recievedBuyRequests);
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

        $properties = $feature->properties;
        $buyer = $buyFeatureRequest->buyer;
        $seller = $buyFeatureRequest->seller;

        $this->releaseAsset($buyFeatureRequest);

        $feature->update(['owner_id' => $buyer->id]);

        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit') ?? 80;
        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit') ?? 110;

        $properties->update([
            'rgb'       => $feature->changeStatusToSoldAndNotPriced(),
            'owner'     => $buyer->name,
            'price_psc' => $buyFeatureRequest->price_psc,
            'price_irr' => $buyFeatureRequest->price_irr,
            'label' => '',
            'minimum_price_percentage' => $buyer->isUnderEighteen() ? $under18PricingLimit : $publicPricingLimit
        ]);

        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();

        $seller->assets->increment($profit->asset, $profit->amount);

        $feature->hourlyProfit->update([
            'user_id' => $buyer->id,
            'amount' => 0,
            'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 86400),
        ]);

        $buyFeatureRequest->update(['status' => '1']);

        $buyFeatureRequest->buyer->traded();
        $buyFeatureRequest->seller->traded();

        $feature->sellRequests->each->update(['status' => 1]);

        $buyFeatureRequest->delete();

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

        broadcast(new FeatureStatusChanged([
            'id'  => $feature->id,
            'rgb' => $feature->properties->rgb,
        ]));

        return new BuyRequestResource($buyFeatureRequest);
    }

    public function rejectBuyRequest(BuyFeatureRequest $buyFeatureRequest)
    {
        $psc_amount = $buyFeatureRequest->lockedAsset->psc;
        $irr_amount = $buyFeatureRequest->lockedAsset->irr;
        $buyer = $buyFeatureRequest->buyer;

        $buyer->assets->increment('psc', $psc_amount);
        $buyer->assets->increment('irr', $irr_amount);

        $buyFeatureRequest->transactions()->delete();
        $buyFeatureRequest->lockedAsset->delete();
        $buyFeatureRequest->delete();
        return response()->noContent();
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(BuyFeatureRequest $buyFeatureRequest)
    {
        $psc_amount = $buyFeatureRequest->lockedAsset->psc;
        $irr_amount = $buyFeatureRequest->lockedAsset->irr;
        $buyer = $buyFeatureRequest->buyer;

        $buyer->assets->increment('psc', $psc_amount);
        $buyer->assets->increment('irr', $irr_amount);

        $buyFeatureRequest->transactions()->delete();
        $buyFeatureRequest->lockedAsset->delete();
        $buyFeatureRequest->delete();
        return response()->noContent();
    }

    private function releaseAsset(BuyFeatureRequest $buyFeatureRequest)
    {
        $psc_amount = $buyFeatureRequest->lockedAsset->psc_amount;
        $irr_amount = $buyFeatureRequest->lockedAsset->irr_amount;

        $buyer = $buyFeatureRequest->buyer;
        $seller = $buyFeatureRequest->seller;

        $psc_total_fee = $psc_amount * config('rgb.fee') * 2;
        $irr_total_fee = $irr_amount * config('rgb.fee') * 2;

        $seller->assets->increment('psc', $psc_amount - $psc_total_fee);
        $seller->assets->increment('irr', $irr_amount - $irr_total_fee);

        $rgb = User::firstWhere('code', 'hm-2000000');

        $rgb->assets->increment('psc', $psc_total_fee);
        $rgb->assets->increment('irr', $irr_total_fee);

        $trade = Trade::create([
            'feature_id' => $buyFeatureRequest->feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => $buyFeatureRequest->price_irr,
            'psc_amount' => $buyFeatureRequest->price_psc,
            'date' => now()
        ]);

        Comission::create([
            'trade_id' => $trade->id,
            'psc' => $psc_total_fee,
            'irr' => $irr_total_fee,
        ]);

        $buyFeatureRequest->transactions->where('user_id', $buyer->id)->each->update(['status' => 1]);

        $trade->transactions()->create([
            'user_id' => $seller->id,
            'asset'  => 'psc',
            'amount' => $psc_amount - $psc_total_fee,
            'action' => 'deposit',
            'status' => 1
        ]);

        $trade->transactions()->create([
            'user_id' => $seller->id,
            'asset' => 'irr',
            'amount' => $irr_amount - $irr_total_fee,
            'action' => 'deposit',
            'status' => 1
        ]);

        $this->cancelOthereRequests($buyFeatureRequest);

        $buyFeatureRequest->lockedAsset->delete();
    }

    private function cancelOthereRequests(BuyFeatureRequest $buyFeatureRequest)
    {
        $feature = $buyFeatureRequest->feature;

        foreach ($feature->buyRequests as $buyRequest) {
            if ($buyRequest->is($buyFeatureRequest)) continue;
            $price_psc = $buyRequest->lockedAsset->psc;
            $price_irr = $buyRequest->lockedAsset->irr;
            $buyRequest->buyer->assets->increment('psc', $price_psc);
            $buyRequest->buyer->assets->increment('irr', $price_irr);
            $buyRequest->lockedAsset->delete();
            $buyRequest->delete();
        }
    }
}
