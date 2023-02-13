<?php

namespace App\Http\Controllers\Api\V1\Feature;

use App\Events\FeatureStatusChanged;
use App\Http\Controllers\Controller;
use App\Helpers\FeatureHelper;
use App\Helpers\AssetHelper;
use App\Http\Resources\FeatureResource;
use App\Models\Trade;
use App\Models\Feature;
use App\Notifications\BuyFeatureNotification;
use Illuminate\Http\JsonResponse;
use App\Models\Comission;
use App\Models\User;
use App\Models\SellFeatureRequest;
use App\Notifications\sellFeature;
use App\Helpers\FeatureIndicators;
use App\Models\LimitedFeaturePurchase;
use App\Models\Feature\FeatureLimit;
use Illuminate\Validation\ValidationException;

class BuyFeatureController extends Controller
{

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
        if ($feature->owner->code === 'hm-2000000') {
            $color = FeatureHelper::getFeatureColor($feature);
            $seller = $feature->owner;
            $featureProperties = $feature->properties;
            $buyer = request()->user();
            $price = $featureProperties->stability;

            if ($price > 0) {
                if (AssetHelper::checkColorBalance($buyer, $feature)) {
                    throw ValidationException::withMessages([
                        'error' => "برای خرید این ملک شما نیاز به {$price} لیتر رنگ {$color} دارید!"
                    ]);
                }
            }

            $buyer->assets->decrement(AssetHelper::getAssetColor($feature), $price);
            $seller->assets->increment(AssetHelper::getAssetColor($feature), $price);

            $feature->update(['owner_id' => $buyer->id]);

            $featureProperties->update([
                'rgb' => FeatureHelper::getSoldAndNotPricedFeatureStatusColor($feature),
                'owner' => $buyer->name,
                'stability' => $featureProperties->area * $featureProperties->density
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
                'asset' => AssetHelper::getAssetColor($feature),
                'amount' => $price,
                'action' => 'withdraw',
                'status' => 1
            ]);

            $time = $buyer->variables->withdraw_profit * 86400;

            $feature->hourlyProfit()->create([
                'user_id' => $buyer->id,
                'asset' => AssetHelper::getAssetColor($feature),
                'dead_line' => now()->addSeconds($time)
            ]);

            broadcast(new FeatureStatusChanged([
                'id' => $feature->id,
                'rgb' => $feature->properties->rgb,
            ]));

            $buyer->notify(new BuyFeatureNotification([
                'feature' => $feature,
                'id' => $feature->properties->id,
                'buyer' => $buyer->name,
                'seller' => "",
                'template' => 'buy-land-metarang'
            ], $trade));
        } else {
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

            if (!iszero($trade->irr_amount) && !iszero($trade->psc_amount)) {
                $trade->transactions()->create([
                    'user_id' => $buyer->id,
                    'asset' => 'psc',
                    'amount' => totalPrice($feature, 'buyer', fee($feature))['psc'],
                    'action' => 'withdraw',
                    'status' => 1
                ]);
                $trade->transactions()->create([
                    'user_id' => $buyer->id,
                    'asset' => 'irr',
                    'amount' => totalPrice($feature, 'buyer', fee($feature))['irr'],
                    'action' => 'withdraw',
                    'status' => 1
                ]);
                $trade->transactions()->create([
                    'user_id' => $seller->id,
                    'asset' => 'psc',
                    'amount' => totalPrice($feature, 'seller', fee($feature))['psc'],
                    'action' => 'deposit',
                    'status' => 1
                ]);
                $trade->transactions()->create([
                    'user_id' => $seller->id,
                    'asset' => 'irr',
                    'amount' => totalPrice($feature, 'seller', fee($feature))['irr'],
                    'action' => 'deposit',
                    'status' => 1
                ]);
            } elseif (!iszero($trade->psc_amount)) {
                $trade->transactions()->create([
                    'user_id' => $buyer->id,
                    'asset' => 'psc',
                    'amount' => totalPrice($feature, 'buyer', fee($feature))['psc'],
                    'action' => 'withdraw',
                    'status' => 1
                ]);
                $trade->transactions()->create([
                    'user_id' => $seller->id,
                    'asset' => 'psc',
                    'amount' => totalPrice($feature, 'seller', fee($feature))['psc'],
                    'action' => 'deposit',
                    'status' => 1
                ]);
            } else {
                $trade->transactions()->create([
                    'user_id' => $buyer->id,
                    'asset' => 'irr',
                    'amount' => totalPrice($feature, 'buyer', fee($feature))['irr'],
                    'action' => 'withdraw',
                    'status' => 1
                ]);
                $trade->transactions()->create([
                    'user_id' => $seller->id,
                    'asset' => 'irr',
                    'amount' => totalPrice($feature, 'seller', fee($feature))['irr'],
                    'action' => 'deposit',
                    'status' => 1
                ]);
            }


            $rgb = User::firstWhere('code', 'hm-2000000');

            $fees = fee($feature);

            $rgb->assets->increment('psc', $fees['psc'] * 2);
            $rgb->assets->increment('irr', $fees['irr'] * 2);

            Comission::create([
                'trade_id' => $trade->id,
                'psc' => $fees['psc'] * 2,
                'irr' => $fees['irr'] * 2,
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
                'dead_line' => now()->addSeconds($buyer->variables->withdraw_profit * 86400),
            ]);

            broadcast(new FeatureStatusChanged([
                'id' => $feature->id,
                'rgb' => FeatureHelper::getSoldAndNotPricedFeatureStatusColor($feature),
            ]));

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
        }
        return new FeatureResource($feature);
    }
}
