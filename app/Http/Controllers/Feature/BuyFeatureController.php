<?php

namespace App\Http\Controllers\Feature;

use App\Events\FeatureTraded;
use App\Http\Controllers\Controller;
use App\Models\FeatureProperties;
use App\Helpers\FeatureHelper;
use App\Helpers\AssetHelper;
use App\Http\Resources\FeatureResource;
use App\Models\Trade;
use App\Models\Feature;
use Illuminate\Http\JsonResponse;

class BuyFeatureController extends Controller
{

    public function show(Feature $feature) {
        return new FeatureResource($feature);
    }

    /**
     * @param Feature $feature
     * @return FeatureResource|JsonResponse
     */
    public function buy(Feature $feature): FeatureResource|\Illuminate\Http\JsonResponse
    {
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

        $time = $buyer->variables->withdraw_profit * 3600;

        $feature->hourlyProfit()->create([
            'user_id' => $buyer->id,
            'asset' => AssetHelper::getAssetColor($feature),
            'dead_line' => now()->addSeconds($time)
        ]);

        event(new FeatureTraded($trade));

        return new FeatureResource($feature);
    }
}
