<?php

namespace App\Http\Controllers\Api\V1\Feature;

use App\Events\FeatureStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Resources\FeatureResource;
use App\Models\Trade;
use App\Models\Feature;
use App\Notifications\BuyFeatureNotification;
use Illuminate\Http\JsonResponse;
use App\Models\Comission;
use App\Models\User;
use App\Models\SellFeatureRequest;
use App\Notifications\sellFeature;
use App\Repositories\FeatureRepository;
use Illuminate\Http\Request;
use App\Models\SystemVariable;

class BuyFeatureController extends Controller
{
    // RGB user
    private $rgb;

    public function __construct(
        private FeatureRepository $featureRepository
    ) {
        // Get RGB user
        $this->rgb = User::firstWhere('code', 'hm-2000000');
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return FeatureResource|JsonResponse
     */
    public function index(Request $request)
    {
        return response()->json(['data' => $this->featureRepository->all($request)]);
    }

    /**
     * Display the specified Feature
     * @param Feature $feature
     * @return FeatureResource
     */
    public function show(Feature $feature)
    {
        return new FeatureResource($feature->load([
            'properties',
            'images',
            'latestTraded',
            'hourlyProfit:id,feature_id,is_active',
            'geometry.coordinates'
        ]));
    }

    /**
     * @param Feature $feature
     * @return FeatureResource|JsonResponse
     */
    public function buy(Feature $feature): FeatureResource|\Illuminate\Http\JsonResponse
    {
        // If the owner of the feature is RGB
        if ($feature->owner->is($this->rgb)) {
            // Call buyFromRGB method
            $this->buyFromRGB($feature);
        } else {
            // Call buyFromUser method
            $this->buyFromUser($feature);
        }
        return new FeatureResource($feature);
    }

    protected function buyFromRGB(Feature $feature)
    {
        // Get public pricing limit
        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit') ?? 80;

        // Get under 18 pricing limit
        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit') ?? 110;

        $color = $feature->getFeatureColor();
        $seller = $feature->owner;
        $featureProperties = $feature->properties;
        $buyer = request()->user();
        $price = $featureProperties->stability;

        // Check if the buyer has enough balance otherwise abort
        if ($buyer->checkColorBalance($feature)) {
            abort(403, "برای خرید این ملک شما نیاز به {$price} لیتر رنگ {$color} دارید!");
        }

        // Withdraw the price from the buyer
        $buyer->wallet->decrement($feature->getColor(), $price);

        // Deposit the price to the seller
        $seller->wallet->increment($feature->getColor(), $price);

        // Update the feature owner
        $feature->update(['owner_id' => $buyer->id]);

        // Update the feature properties
        $featureProperties->update([
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
            'owner' => $buyer->name,
            'label' => '',
            // If buyer is under 18 set the minimum price percentage to under 18 pricing limit otherwise set it to public pricing limit
            'minimum_price_percentage' => $buyer->isUnderEighteen() ? $under18PricingLimit : $publicPricingLimit
        ]);

        // Create a new trade
        $trade = Trade::create([
            'feature_id' => $feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => 0,
            'psc_amount' => 0,
        ]);

        // Create a new transaction for the buyer
        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => $feature->getColor(),
            'amount' => $price,
            'action' => 'withdraw',
            'status' => 1
        ]);

        // Get buyer widthdraw profit time
        $time = $buyer->variables->withdraw_profit * 86400;

        // Create a new hourly profit for the buyer
        $feature->hourlyProfit()->create([
            'user_id' => $buyer->id,
            'asset' => $feature->getColor(),
            'dead_line' => now()->addSeconds($time)
        ]);

        // Notify the buyer about their purchase
        $buyer->notify(new BuyFeatureNotification([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => "",
            'template' => 'buy-land-metarang'
        ], $trade));

        // Broadcast the feature status change
        broadcast(new FeatureStatusChanged([
            'id' => $feature->id,
            'rgb' => $feature->properties->rgb,
        ]));
    }

    /**
     * @param Feature $feature
     */
    protected function buyFromUser(Feature $feature)
    {
        // Check if the feature is under priced
        if ($feature->underPriced()) {
            // Get the latest under priced sell request for the owner of this feature
            $latestUnderPricedRequest = SellFeatureRequest::latestUnderPriceRequests($feature->owner, $feature)->last();

            // Check if the latest under priced request is not null
            if ($latestUnderPricedRequest) {
                // Get the latest feature trade
                $featureTrade = Trade::latestFeatureTrades($latestUnderPricedRequest->feature)->last();

                // Check if the trade time is less than 24 hours
                if ($featureTrade->created_at->addHours(24) > now()) {

                    // Check if the trade time is less than 1 hour
                    if ($featureTrade->created_at->diffInHours(now()) < 1) {

                        // Get the elapsed time and label it as minutes
                        $elapsedTime = $featureTrade->created_at->addDays(1)->diffInMinutes(now()) . ' دقیقه';
                    } else {
                        // Get the elapsed time and label it as hours
                        $elapsedTime = $featureTrade->created_at->addDays(1)->diffInHours(now()) . ' ساعت';
                    }
                    // Abort with 403 status code and the message
                    abort(403, 'این ملک زیر قیمت 100% قیمت گذاری شده است برای خرید آن بعد از ' . $elapsedTime . ' دوباره تلاش کنید');
                }
            }
        }

        $seller = $feature->owner;
        $buyer = request()->user();

        // Check buyer balance
        $buyer->checkBalance($feature);

        // Charge the buyer
        $this->chargeBuyer($buyer, $feature);

        // Pay the seller
        $this->paySeller($seller, $feature);

        // Create a new trade
        $trade = Trade::create([
            'feature_id' => $feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => $feature->properties->price_irr,
            'psc_amount' => $feature->properties->price_psc,
            'date' => now()
        ]);

        // Create a new transaction for the buyer with the psc currency
        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'psc',
            'amount' => $this->buyerChargeAmount($feature, 'price_psc'),
            'action' => 'withdraw',
            'status' => 1
        ]);

        // Create a new transaction for the buyer with the irr currency
        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'irr',
            'amount' => $this->buyerChargeAmount($feature, 'price_irr'),
            'action' => 'withdraw',
            'status' => 1
        ]);

        // Create a new transaction for the seller with the psc currency
        $trade->transactions()->create([
            'user_id' => $seller->id,
            'asset' => 'psc',
            'amount' => $this->sellerPayAmount($feature, 'price_psc'),
            'action' => 'deposit',
            'status' => 1
        ]);

        // Create a new transaction for the seller with the irr currency
        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'irr',
            'amount' => $this->sellerPayAmount($feature, 'price_irr'),
            'action' => 'deposit',
            'status' => 1
        ]);

        // Increment the psc and irr wallet of the system
        $this->rgb->wallet->increment('psc', $this->fee($feature, 'price_psc') * 2);
        $this->rgb->wallet->increment('irr', $this->fee($feature, 'price_irr') * 2);

        // Create a new comission for the system
        Comission::create([
            'trade_id' => $trade->id,
            'psc' => $this->fee($feature, 'price_psc') * 2,
            'irr' => $this->fee($feature, 'price_irr') * 2,
        ]);

        // Update the feature owner
        $feature->update(['owner_id' => $buyer->id]);

        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit') ?? 80;
        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit') ?? 110;

        // Update the feature properties
        $feature->properties->update([
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
            'owner' => $buyer->name,
            'label' => '',
            // If the buyer is under 18, the minimum price percentage is 110% of the public pricing limit
            'minimum_price_percentage' => $buyer->isUnderEighteen() ? $under18PricingLimit : $publicPricingLimit
        ]);

        // Set all pending sell request status to 1
        $feature->sellRequests->where('status', 0)
            ->where('seller_id', $seller->id)
            ->each->update(['status', 1]);

        // Cancel all buy requests
        $this->cancelBuyRequests($feature);

        // Raise the traded event for the buyer and seller
        $buyer->traded();
        $seller->traded();

        // Get the hourly profit of the feature
        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();

        // Increment the seller wallet
        $seller->wallet->increment($profit->asset, $profit->amount);

        // Update the seller hourly profit
        $feature->hourlyProfit->update([
            'user_id' => $buyer->id,
            'amount' => 0,
            'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 86400),
        ]);

        // Notfiy the buyer about the trade
        $buyer->notify(new BuyFeatureNotification([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => $seller->name,
            'template' => 'buy-land-user',
        ], $trade));

        // Notfiy the seller about the trade
        $seller->notify(new sellFeature([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => $seller->name,
            'template' => 'sell-land',
        ], $trade));

        // Broadcast the feature status change
        broadcast(new FeatureStatusChanged([
            'id' => $feature->id,
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
        ]));
    }

    /**
     * Charge the buyer
     *
     * @param User $buyer
     * @param Feature $feature
     * @return void
     */
    protected function chargeBuyer(User $buyer, Feature $feature): void
    {
        $buyer->wallet->decrement('psc', $this->buyerChargeAmount($feature, 'price_psc'));
        $buyer->wallet->decrement('irr', $this->buyerChargeAmount($feature, 'price_irr'));
    }

    /**
     * Pay the seller
     *
     * @param User $seller
     * @param Feature $feature
     * @return void
     */
    protected function paySeller(User $seller, Feature $feature): void
    {
        $seller->wallet->increment('psc', $this->sellerPayAmount($feature, 'price_psc'));
        $seller->wallet->increment('irr', $this->sellerPayAmount($feature, 'price_irr'));
    }

    /**
     * Get the buyer charge amount
     *
     * @param Feature $feature
     * @param string $currency
     * @return float
     */
    protected function buyerChargeAmount(Feature $feature, $currency): float
    {
        return $feature->properties->{$currency} + $this->fee($feature, $currency);
    }

    /**
     * Get the seller pay amount
     *
     * @param Feature $feature
     * @param string $currency
     * @return float
     */
    protected function sellerPayAmount(Feature $feature, $currency): float
    {
        return $feature->properties->{$currency} - $this->fee($feature, $currency);
    }

    /**
     * Get the fee amount
     *
     * @param Feature $feature
     * @param string $currency
     * @return float
     */
    protected function fee(Feature $feature, $currency): float
    {
        return  $feature->properties->{$currency} * config('rgb.fee');
    }

    /**
     * Cancel all buy requests
     *
     * @param Feature $feature
     * @return void
     */
    protected function cancelBuyRequests(Feature $feature): void
    {
        $requests = $feature->buyRequests;

        if ($requests) {
            foreach ($requests as $request) {
                $buyer = $request->buyer;
                $lockedAsset = $request->lockedAsset;
                $buyer->wallet->increment('psc', $lockedAsset->psc);
                $buyer->wallet->increment('irr', $lockedAsset->irr);
                $request->lockedAsset->delete();
                $request->delete();
            }
        }
    }
}
