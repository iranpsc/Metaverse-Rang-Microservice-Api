<?php

namespace App\Http\Controllers\Feature;

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
        if ($feature->owner->code == 'hm-2000000') {
            $color = FeatureHelper::getFeatureColor($feature);
            $seller = $feature->owner;
            $featureProperties = $feature->properties;
            $buyer = request()->user();
            $price = $featureProperties->stability;

            if (AssetHelper::checkColorBalance($buyer, $feature)) {
                return response()->json(['error' => "موجودی شما کافی نیست. شما باید در ابتدا موجودی رنگ {$color} حساب خود را افزایش دهید"]);
            }

            $buyer->assets->decrement(AssetHelper::getAssetColor($feature), $price);
            $seller->assets->increment(AssetHelper::getAssetColor($feature), $price);

            $feature->update(['owner_id' => $buyer->id]);

            $featureProperties->update([
                'rgb' => FeatureHelper::getSoldAndNotPricedFeatureStatusColor($feature),
                'owner' => $buyer->name,
            ]);

            $message = 'خرید با موفقیت انجام شد';
            $feature->message = $message;

            $trade = Trade::create([
                'feature_id' => $feature->id,
                'buyer_id' => $buyer->id,
                'seller_id' => $seller->id,
                'irr_amount' => 0,
                'psc_amount' => 0,
            ]);

            $time = $buyer->variables->withdraw_profit * 86400;

            $feature->hourlyProfit()->create([
                'user_id' => $buyer->id,
                'asset' => AssetHelper::getAssetColor($feature),
                'dead_line' => now()->addSeconds($time)
            ]);

            broadcast(new FeatureStatusChanged([
                'id' => $feature->properties->id,
                'rgb' => $feature->properties->rgb,
            ]));

            $buyer->notify(new BuyFeatureNotification([
                'feature' => $feature,
                'id' => $feature->properties->id,
                'buyer' => $buyer->name,
                'seller' => "",
                'template' => 'buy-land-metarang'
            ]));
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

            $message = 'خرید با موفقیت انجام شد';
            $feature->message = $message;
            broadcast(new FeatureStatusChanged([
                'id' => $feature->properties->id,
                'rgb' => FeatureHelper::getSoldAndNotPricedFeatureStatusColor($feature),
            ]));

            $buyer->notify(new BuyFeatureNotification([
                'feature' => $feature,
                'id' => $feature->properties->id,
                'buyer' => $buyer->name,
                'seller' => $seller->name,
                'template' => 'buy-land-user',
            ]));
        }
        return new FeatureResource($feature);
    }
}
