<?php

namespace App\Http\Controllers\Api\V1\Feature;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\HourlyProfitResource;
use App\Models\Feature\FeatureHourlyProfit;
use App\Notifications\FeatureHourlyProfitDeposit;

class FeatureHourlyProfitController extends Controller
{
    public function index()
    {
        return HourlyProfitResource::collection(FeatureHourlyProfit::whereBelongsTo(request()->user())->get());
    }

    public function getProfitsByApplication(Request $request)
    {
        $request->validate(['karbari' => 'required|in:m,t,a']);

        $user = $request->user();

        $time = $user->variables->withdraw_profit * 86400;

        FeatureHourlyProfit::whereBelongsTo($user)->with('feature', 'feature.properties')
        ->chunkById(100, function ($profits) use ($request, $user, $time) {
            foreach ($profits as $profit) {
                if ($profit->feature->properties->karbari == $request->karbari) {
                    $user->assets->increment($profit->asset, $profit->amount);
                    $user->notify(new FeatureHourlyProfitDeposit([
                        'asset' => $profit->asset,
                        'amount' => $profit->amount,
                        'id' => $profit->feature->properties->id,
                    ]));
                    $profit->update([
                        'amount' => 0,
                        'dead_line' => now()->addSeconds($time)
                    ]);
                }
            }
        });
        return HourlyProfitResource::collection(FeatureHourlyProfit::whereBelongsTo(request()->user())->get());
    }

    public function getSingleProfit(FeatureHourlyProfit $featureHourlyProfit)
    {
        $feature = $featureHourlyProfit->feature;
        $user = request()->user();

        $user->assets->increment($featureHourlyProfit->asset, $featureHourlyProfit->amount);
        $time = $user->variables->withdraw_profit * 86400;

        $user->notify(new FeatureHourlyProfitDeposit([
            'asset' => $feature->getColor(),
            'amount' => $featureHourlyProfit->amount,
            'id' => $feature->properties->id,
        ]));

        $featureHourlyProfit->update([
            'amount' => 0,
            'dead_line' => now()->addSeconds($time)
        ]);

        return new HourlyProfitResource($featureHourlyProfit);
    }
}
