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
use App\Helpers\FeatureIndicators;
use App\Models\BuyFeatureRequest;
use App\Models\Feature\FeatureLimit;
use App\Models\LimitedFeaturePurchase;

class BuyFeatureController extends Controller
{
    private $rgb;

    private $limitedFeatures;

    public function __construct(
        private FeatureRepository $featureRepository
    ) {
        $this->rgb = User::firstWhere('code', 'hm-2000000');

        $this->limitedFeatures = [
            FeatureIndicators::MaskoniTradingLimited,
            FeatureIndicators::TejariTradingLimited,
            FeatureIndicators::AmoozeshiTradingLimited,
        ];
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
     *
     * @param Feature $feature
     * @return FeatureResource
     */
    public function show(Feature $feature)
    {
        $feature->load([
            'properties',
            'images',
            'latestTraded.seller',
            'hourlyProfit:id,feature_id,is_active',
            'buildingModels' => function ($query) {
                $query->withPivot('construction_end_date');
            }
        ]);

        return new FeatureResource($feature);
    }

    /**
     * Buy a feature
     *
     * @param Feature $feature
     * @return FeatureResource
     */
    public function buy(Feature $feature): FeatureResource
    {
        $feature->load('properties', 'owner');

        if (in_array($feature->properties->rgb, $this->limitedFeatures)) {
            $this->handleLimitedFeature($feature);
        } elseif ($feature->owner->is($this->rgb)) {
            $this->buyFromRGB($feature);
        } else {
            $this->buyFromUser($feature);
        }

        return new FeatureResource($feature);
    }

    protected function handleLimitedFeature(Feature $feature)
    {
        // Get the feature limitation
        $featureLimitation = $this->getLimitation($feature);

        abort_if(is_null($featureLimitation), 400, 'خطایی رخ داده است. لطفا با پشتیبانی تماس بگیرید.');

        $buyer = request()->user();
        $seller = $feature->owner;
        $price = $feature->properties->stability;
        $color = $feature->getFeatureColor();

        if ($featureLimitation->price_limit && $feature->price != 0) {
            if ($buyer->checkColorBalance($feature)) {
                abort(403, "برای خرید این ملک شما نیاز به {$price} لیتر رنگ {$color} دارید!");
            }
        }

        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit') ?? 80;

        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit') ?? 110;

        $featureProperties = $feature->properties;

        $buyer->wallet->decrement($feature->getColor(), $price);

        $seller->wallet->increment($feature->getColor(), $price);

        $feature->update(['owner_id' => $buyer->id]);

        $featureProperties->update([
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
            'owner' => $buyer->name,
            'label' => '',
            'stability' => $featureProperties->area * $featureProperties->density,
            'minimum_price_percentage' => $buyer->isUnderEighteen() ? $under18PricingLimit : $publicPricingLimit
        ]);

        if ($featureLimitation->individual_buy_limit) {
            LimitedFeaturePurchase::create([
                'user_id' => $buyer->id,
                'feature_limit_id' => $featureLimitation->id
            ]);
        }

        $trade = Trade::create([
            'feature_id' => $feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => 0,
            'psc_amount' => 0,
        ]);

        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => $feature->getColor(),
            'amount' => $price,
            'action' => 'withdraw',
            'status' => 0
        ]);

        $time = $buyer->variables->withdraw_profit * 86400;

        $feature->hourlyProfit()->create([
            'user_id' => $buyer->id,
            'asset' => $feature->getColor(),
            'dead_line' => now()->addSeconds($time),
            'is_active' => $featureLimitation->price_limit && $featureLimitation->price == 0 ? false : true,
        ]);

        $buyer->notify(new BuyFeatureNotification([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => "",
        ], $trade));

        broadcast(new FeatureStatusChanged([
            'id' => $feature->id,
            'rgb' => $feature->properties->rgb,
        ]));
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

    protected function buyFromRGB(Feature $feature)
    {
        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit') ?? 80;

        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit') ?? 110;

        $color = $feature->getFeatureColor();
        $seller = $feature->owner;
        $featureProperties = $feature->properties;
        $buyer = request()->user();
        $price = $featureProperties->stability;

        if ($buyer->checkColorBalance($feature)) {
            abort(403, "برای خرید این ملک شما نیاز به {$price} لیتر رنگ {$color} دارید!");
        }

        $buyer->wallet->decrement($feature->getColor(), $price);

        $seller->wallet->increment($feature->getColor(), $price);

        $feature->update(['owner_id' => $buyer->id]);

        $featureProperties->update([
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
            'owner' => $buyer->name,
            'label' => '',
            'minimum_price_percentage' => $buyer->isUnderEighteen() ? $under18PricingLimit : $publicPricingLimit
        ]);

        $trade = Trade::create([
            'feature_id' => $feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => 0,
            'psc_amount' => 0,
        ]);

        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => $feature->getColor(),
            'amount' => $price,
            'action' => 'withdraw',
            'status' => 0
        ]);

        $time = $buyer->variables->withdraw_profit * 86400;

        $feature->hourlyProfit()->create([
            'user_id' => $buyer->id,
            'asset' => $feature->getColor(),
            'dead_line' => now()->addSeconds($time)
        ]);

        $buyer->notify(new BuyFeatureNotification([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => "",
        ], $trade));

        broadcast(new FeatureStatusChanged([
            'id' => $feature->id,
            'rgb' => $feature->properties->rgb,
        ]));
    }

    /**
     * Buy a feature from a user
     *
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
                }
            }
        }

        $seller = $feature->owner;
        $buyer = request()->user();

        $buyer->checkBalance($feature);

        $this->chargeBuyer($buyer, $feature);

        $this->paySeller($seller, $feature);

        $trade = Trade::create([
            'feature_id' => $feature->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'irr_amount' => $feature->properties->price_irr,
            'psc_amount' => $feature->properties->price_psc,
            'date' => now()
        ]);

        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'psc',
            'amount' => $this->buyerChargeAmount($feature, 'price_psc'),
            'action' => 'withdraw',
            'status' => 0
        ]);

        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'irr',
            'amount' => $this->buyerChargeAmount($feature, 'price_irr'),
            'action' => 'withdraw',
            'status' => 0
        ]);

        $trade->transactions()->create([
            'user_id' => $seller->id,
            'asset' => 'psc',
            'amount' => $this->sellerPayAmount($feature, 'price_psc'),
            'action' => 'deposit',
            'status' => 0
        ]);

        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'irr',
            'amount' => $this->sellerPayAmount($feature, 'price_irr'),
            'action' => 'deposit',
            'status' => 0
        ]);

        $this->rgb->wallet->increment('psc', $this->fee($feature, 'price_psc') * 2);
        $this->rgb->wallet->increment('irr', $this->fee($feature, 'price_irr') * 2);

        Comission::create([
            'trade_id' => $trade->id,
            'psc' => $this->fee($feature, 'price_psc') * 2,
            'irr' => $this->fee($feature, 'price_irr') * 2,
        ]);

        $feature->update(['owner_id' => $buyer->id]);

        $publicPricingLimit = SystemVariable::getByKey('public_pricing_limit') ?? 80;
        $under18PricingLimit = SystemVariable::getByKey('under_18_pricing_limit') ?? 110;

        $feature->properties->update([
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
            'owner' => $buyer->name,
            'label' => '',
            // If the buyer is under 18, the minimum price percentage is 110% of the public pricing limit
            'minimum_price_percentage' => $buyer->isUnderEighteen() ? $under18PricingLimit : $publicPricingLimit
        ]);

        $feature->sellRequests->where('status', 0)
            ->where('seller_id', $seller->id)
            ->each->update(['status', 1]);

        $this->cancelBuyRequests($feature);

        $buyer->traded();
        $seller->traded();

        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();

        $seller->wallet->increment($profit->asset, $profit->amount);

        $feature->hourlyProfit->update([
            'user_id' => $buyer->id,
            'amount' => 0,
            'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 86400),
            'is_active' => true,
        ]);

        $buyer->notify(new BuyFeatureNotification([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => $seller->name,
        ], $trade));

        $seller->notify(new sellFeature([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => $seller->name,
        ], $trade));

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
