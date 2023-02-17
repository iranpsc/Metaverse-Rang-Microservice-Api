<?php

namespace App\Http\Controllers\Api\V1\Feature;

use App\Events\FeatureStatusChanged;
use App\Exceptions\InsufficientBalanceException;
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

class BuyFeatureController extends Controller
{
    private $rgb;

    public function __construct(
        private FeatureRepository $featureRepository
    ) {
        $this->middleware(['account.security', 'auth:sanctum', 'verified'])
            ->except('index', 'show');
        $this->rgb = User::firstWhere('code', 'hm-2000000');
    }

    public function index(Request $request)
    {
        return response()->json(['date' => $this->featureRepository->getFeaturesByCoordinates($request)]);
    }

    public function show(Feature $feature)
    {
        return new FeatureResource($feature);
    }

    /**
     * @param Feature $feature
     * @return FeatureResource|JsonResponse
     */
    public function buy(Feature $feature): FeatureResource|\Illuminate\Http\JsonResponse
    {
        if ($feature->owner->is($this->rgb)) {
            $this->buyFromRGB($feature);
        } else {
            $this->buyFromUser($feature);
        }
        return new FeatureResource($feature);
    }

    protected function buyFromRGB(Feature $feature)
    {
        $color = $feature->getFeatureColor();
        $seller = $feature->owner;
        $featureProperties = $feature->properties;
        $buyer = request()->user();
        $price = $featureProperties->stability;

        if ($buyer->checkColorBalance($feature)) {
            throw new InsufficientBalanceException("برای خرید این ملک شما نیاز به {$price} لیتر رنگ {$color} دارید!");
        }

        $buyer->assets->decrement($feature->getColor(), $price);
        $seller->assets->increment($feature->getColor(), $price);

        $feature->update(['owner_id' => $buyer->id]);

        $featureProperties->update([
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
            'owner' => $buyer->name,
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
            'status' => 1
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
            'template' => 'buy-land-metarang'
        ], $trade));

        broadcast(new FeatureStatusChanged([
            'id' => $feature->id,
            'rgb' => $feature->properties->rgb,
        ]));
    }

    protected function buyFromUser(Feature $feature)
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
            'status' => 1
        ]);
        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'irr',
            'amount' => $this->buyerChargeAmount($feature, 'price_irr'),
            'action' => 'withdraw',
            'status' => 1
        ]);

        $trade->transactions()->create([
            'user_id' => $seller->id,
            'asset' => 'psc',
            'amount' => $this->sellerPayAmount($feature, 'price_psc'),
            'action' => 'deposit',
            'status' => 1
        ]);
        $trade->transactions()->create([
            'user_id' => $buyer->id,
            'asset' => 'irr',
            'amount' => $this->sellerPayAmount($feature, 'price_irr'),
            'action' => 'deposit',
            'status' => 1
        ]);

        $this->rgb->assets->increment('psc', $this->fee($feature, 'price_psc') * 2);
        $this->rgb->assets->increment('irr', $this->fee($feature, 'price_irr') * 2);

        Comission::create([
            'trade_id' => $trade->id,
            'psc' => $this->fee($feature, 'price_psc') * 2,
            'irr' => $this->fee($feature, 'price_irr') * 2,
        ]);

        $feature->update(['owner_id' => $buyer->id]);

        $feature->properties->update([
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
            'owner' => $buyer->name,
        ]);

        $feature->sellRequests->where('status', 0)
            ->where('seller_id', $seller->id)
            ->each->update(['status', 1]);

        $this->cancelBuyRequests($feature);

        $buyer->traded();
        $seller->traded();

        $profit = $feature->hourlyProfit->where('user_id', $seller->id)->first();

        $seller->assets->increment($profit->asset, $profit->amount);

        $feature->hourlyProfit->update([
            'user_id' => $buyer->id,
            'amount' => 0,
            'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 86400),
        ]);

        $buyer->notify(new BuyFeatureNotification([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => $seller->name,
            'template' => 'buy-land-user',
        ], $trade));

        $seller->notify(new sellFeature([
            'feature' => $feature,
            'id' => $feature->properties->id,
            'buyer' => $buyer->name,
            'seller' => $seller->name,
            'template' => 'sell-land',
        ], $trade));

        broadcast(new FeatureStatusChanged([
            'id' => $feature->id,
            'rgb' => $feature->changeStatusToSoldAndNotPriced(),
        ]));
    }

    protected function chargeBuyer(User $buyer, Feature $feature)
    {
        $buyer->assets->decrement('psc', $this->buyerChargeAmount($feature, 'price_psc'));
        $buyer->assets->decrement('irr', $this->buyerChargeAmount($feature, 'price_irr'));
    }

    protected function paySeller(User $seller, Feature $feature)
    {
        $seller->assets->increment('psc', $this->sellerPayAmount($feature, 'price_psc'));
        $seller->assets->increment('irr', $this->sellerPayAmount($feature, 'price_irr'));
    }

    protected function buyerChargeAmount(Feature $feature, $currency)
    {
        return $feature->properties->{$currency} + $this->fee($feature, $currency);
    }

    protected function sellerPayAmount(Feature $feature, $currency)
    {
        return $feature->properties->{$currency} - $this->fee($feature, $currency);
    }

    protected function fee(Feature $feature, $currency)
    {
        return  $feature->properties->{$currency} * config('rgb.fee');
    }

    protected function cancelBuyRequests(Feature $feature)
    {
        $requests = $feature->buyRequests;

        if ($requests) {
            foreach ($requests as $request) {
                $buyer = $request->buyer;
                $lockedAsset = $request->lockedAsset;
                $buyer->assets->increment('psc', $lockedAsset->psc);
                $buyer->assets->increment('irr', $lockedAsset->irr);
                $request->lockedAsset->delete();
                $request->delete();
            }
        }
    }
}
