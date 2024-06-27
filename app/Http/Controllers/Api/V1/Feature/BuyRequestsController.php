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
     * Display a listing of the Features.
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
        // Get the buyer and seller from the request
        $buyer = request()->user();
        $seller = $feature->owner;

        // Get the prices from the request
        $price_psc = $request->price_psc;
        $price_irr = $request->price_irr;

        // Get the floor price percentage from the feature's properties
        $floor_price_percentage = $feature->properties->minimum_price_percentage;

        // Calculate the total requested price and the total feature price
        $totalRequestedPrice = $price_irr + $price_psc * Variable::getRate('psc');
        $totalFeaturePrice = $feature->properties->stability * Variable::getRate($feature->getColor());

        // Check if the requested price is below the floor price percentage
        if ($totalRequestedPrice / $totalFeaturePrice * 100 < $floor_price_percentage) {
            abort(403, sprintf("شما به مجاز به ارسال درخواست خرید به کمتر از %s قیمت ملک نمی باشید!", $floor_price_percentage));
        }

        // Check if the buyer has enough PSC balance
        if ($buyer->wallet->psc < $price_psc + $price_psc * config('rgb.fee')) {
            throw ValidationException::withMessages([
                'price_psc' => 'موجودی psc شما کافی نیست!'
            ]);
        }
        // Check if the buyer has enough IRR balance
        elseif ($buyer->wallet->irr < $price_irr + $price_irr * config('rgb.fee')) {
            throw ValidationException::withMessages([
                'price_irr' => 'موجودی ریال شما کافی نیست!'
            ]);
        }

        // Create a new BuyFeatureRequest in the database
        $buyFeatureRequest = BuyFeatureRequest::create([
            'buyer_id'   => $buyer->id,
            'seller_id'  => $seller->id,
            'feature_id' => $feature->id,
            'note'       => $request->input('note', ''),
            'price_psc'  => $price_psc,
            'price_irr'  => $price_irr,
        ]);

        // Add the fee to the prices
        $price_psc = $price_psc + $price_psc * config('rgb.fee');
        $price_irr = $price_irr + $price_irr * config('rgb.fee');

        // Decrement the buyer's PSC and IRR balances
        $buyer->wallet->decrement('psc', $price_psc);
        $buyer->wallet->decrement('irr', $price_irr);

        // Create a lockedwallet record for the buyer
        $buyer->lockedwallet()->create([
            'buy_feature_request_id' => $buyFeatureRequest->id,
            'feature_id'             => $buyFeatureRequest->feature->id,
            'psc'                    => $price_psc,
            'irr'                    => $price_irr
        ]);

        // Create withdrawal transactions for the buyer
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

        // Send a notification to the buyer
        $buyer->notify(new BuyRequestNotification([
            'id'         => $feature->properties->id,
            'price_psc'  => $buyFeatureRequest->price_psc,
            'price_irr'  => $buyFeatureRequest->price_irr,
            'buyRequest' => $buyFeatureRequest,
            'type'       => 'buyer'
        ]));

        // Send a notification to the seller
        $seller->notify(new BuyRequestNotification([
            'id'         => $feature->properties->id,
            'price_psc'  => $buyFeatureRequest->price_psc,
            'price_irr'  => $buyFeatureRequest->price_irr,
            'buyRequest' => $buyFeatureRequest,
            'type'       => 'seller'
        ]));

        // Return the BuyFeatureRequest as a resource
        return new BuyRequestResource($buyFeatureRequest);
    }

    /**
     * @param BuyFeatureRequest $buyFeatureRequest
     * @return JsonResponse|BuyRequestResource
     */
    public function recievedBuyRequests()
    {
        return BuyRequestResource::collection(request()->user()->recievedBuyRequests);
    }

    /**
     * @param BuyFeatureRequest $buyFeatureRequest
     * @return JsonResponse|BuyRequestResource
     */
    public function acceptBuyRequest(BuyFeatureRequest $buyFeatureRequest)
    {
        $feature = $buyFeatureRequest->feature;

        // Check if the feature is underpriced
        if ($feature->underPriced()) {
            // Get the latest underpriced sell request for the owner of this feature
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

        // Release the locked asset for the buyer
        $this->releaseAsset($buyFeatureRequest);

        // Update the feature's owner to the buyer
        $feature->update(['owner_id' => $buyer->id]);

        // Update the feature's properties
        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit') ?? 80;
        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit') ?? 110;
        $properties->update([
            'rgb'       => $feature->changeStatusToSoldAndNotPriced(),
            'owner'     => $buyer->name,
            'price_psc' => $buyFeatureRequest->price_psc,
            'price_irr' => $buyFeatureRequest->price_irr,
            'label'     => '',
            'minimum_price_percentage' => $buyer->isUnderEighteen() ? $under18PricingLimit : $publicPricingLimit
        ]);

        // Update the seller's wallet based on the hourly profit
        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();
        $seller->wallet->increment($profit->asset, $profit->amount);

        // Update the hourly profit for the buyer
        $feature->hourlyProfit->update([
            'user_id'    => $buyer->id,
            'amount'     => 0,
            'dead_line'  => now()->addSeconds($buyer->variables->withdraw_profit * 86400),
        ]);

        // Update the status of the buy feature request
        $buyFeatureRequest->update(['status' => '1']);

        // Update the traded status for the buyer and seller
        $buyFeatureRequest->buyer->traded();
        $buyFeatureRequest->seller->traded();

        // Update the status of all sell requests related to the feature
        $feature->sellRequests->each->update(['status' => 1]);

        // Delete the buy feature request
        $buyFeatureRequest->delete();

        // Send a notification to the buyer
        $buyFeatureRequest->buyer->notify(new BuyFeatureNotification([
            'feature'   => $feature,
            'id'        => $feature->properties->id,
            'buyer'     => $buyFeatureRequest->buyer->name,
            'seller'    => $buyFeatureRequest->seller->name,
            'template'  => 'sell-land',
        ], $feature->latestTraded));

        // Send a notification to the seller
        $buyFeatureRequest->seller->notify(new sellFeature([
            'feature'   => $feature,
            'id'        => $feature->properties->id,
            'buyer'     => $buyFeatureRequest->buyer->name,
            'seller'    => $buyFeatureRequest->seller->name,
            'template'  => 'buy-land-user',
        ], $feature->latestTraded));

        // Broadcast an event for the changed feature status
        broadcast(new FeatureStatusChanged([
            'id'  => $feature->id,
            'rgb' => $feature->properties->rgb,
        ]));

        // Return the BuyFeatureRequest as a resource
        return new BuyRequestResource($buyFeatureRequest);
    }

    /**
     * @param BuyFeatureRequest $buyFeatureRequest
     * @return JsonResponse
     */
    public function rejectBuyRequest(BuyFeatureRequest $buyFeatureRequest)
    {
        // Get the locked asset for the buyer
        $psc_amount = $buyFeatureRequest->lockedAsset->psc;
        $irr_amount = $buyFeatureRequest->lockedAsset->irr;
        $buyer = $buyFeatureRequest->buyer;

        // Release the locked asset for the buyer
        $buyer->wallet->increment('psc', $psc_amount);
        $buyer->wallet->increment('irr', $irr_amount);

        // Delete the buy request transactions, locked asset and the buy request itself
        $buyFeatureRequest->transactions()->delete();
        $buyFeatureRequest->lockedAsset->delete();
        $buyFeatureRequest->delete();
        return response()->noContent(200);
    }


    /**
     * Delete the buy feature request
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(BuyFeatureRequest $buyFeatureRequest)
    {
        // Get the locked asset for the buyer
        $psc_amount = $buyFeatureRequest->lockedAsset->psc;
        $irr_amount = $buyFeatureRequest->lockedAsset->irr;
        $buyer = $buyFeatureRequest->buyer;

        // Release the locked asset for the buyer
        $buyer->wallet->increment('psc', $psc_amount);
        $buyer->wallet->increment('irr', $irr_amount);

        // Delete the buy request transactions, locked asset and the buy request itself
        $buyFeatureRequest->transactions()->delete();
        $buyFeatureRequest->lockedAsset->delete();
        $buyFeatureRequest->delete();
        return response()->noContent();
    }

    /**
     * Release the locked asset for the buyer
     *
     * @param BuyFeatureRequest $buyFeatureRequest
     */
    private function releaseAsset(BuyFeatureRequest $buyFeatureRequest)
    {
        $psc_amount = $buyFeatureRequest->lockedAsset->psc;
        $psc_amount = $buyFeatureRequest->price_psc;
        $irr_amount = $buyFeatureRequest->price_irr;

        $buyer = $buyFeatureRequest->buyer;
        $seller = $buyFeatureRequest->seller;

        // Calculate the fee
        $pscFee = $psc_amount * config('rgb.fee');
        $irrFee = $irr_amount * config('rgb.fee');

        // Add the feature price to the seller's wallet
        $seller->wallet->increment('psc', $psc_amount - $pscFee);
        $seller->wallet->increment('irr', $irr_amount - $irrFee);

        // Get the rgb user
        $rgb = User::firstWhere('code', 'hm-2000000');

        // Add the fee to the rgb's wallet
        $rgb->wallet->increment('psc', $pscFee * 2);
        $rgb->wallet->increment('irr', $irrFee * 2);

        // Create a trade
        $trade = Trade::create([
            'feature_id' => $buyFeatureRequest->feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => $buyFeatureRequest->price_irr,
            'psc_amount' => $buyFeatureRequest->price_psc,
            'date' => now()
        ]);

        // Create a comission
        Comission::create([
            'trade_id' => $trade->id,
            'psc' => $pscFee * 2,
            'irr' => $irrFee * 2,
        ]);

        // Update the status of the buyer transactions to 1
        $buyFeatureRequest->transactions->where('user_id', $buyer->id)->each->update(['status' => 1]);

        // Create a transaction for the seller
        $trade->transactions()->create([
            'user_id' => $seller->id,
            'asset'  => 'psc',
            'amount' => $psc_amount - $pscFee,
            'action' => 'deposit',
            'status' => 1
        ]);

        $trade->transactions()->create([
            'user_id' => $seller->id,
            'asset' => 'irr',
            'amount' => $irr_amount - $irrFee,
            'action' => 'deposit',
            'status' => 1
        ]);

        // Cancel other requests
        $this->cancelOthereRequests($buyFeatureRequest);

        // Delete the locked asset record from the database
        $buyFeatureRequest->lockedAsset->delete();
    }

    /**
     * Cancel other requests
     *
     * @param BuyFeatureRequest $buyFeatureRequest
     */
    private function cancelOthereRequests(BuyFeatureRequest $buyFeatureRequest)
    {
        $feature = $buyFeatureRequest->feature;

        foreach ($feature->buyRequests as $buyRequest) {
            // Skip the current request
            if ($buyRequest->is($buyFeatureRequest)) continue;
            $price_psc = $buyRequest->lockedAsset->psc;
            $price_irr = $buyRequest->lockedAsset->irr;
            $buyRequest->buyer->wallet->increment('psc', $price_psc);
            $buyRequest->buyer->wallet->increment('irr', $price_irr);
            $buyRequest->lockedAsset->delete();
            $buyRequest->delete();
        }
    }
}
