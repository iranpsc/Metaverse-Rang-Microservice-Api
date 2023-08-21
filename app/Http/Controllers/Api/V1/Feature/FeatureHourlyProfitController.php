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
        return HourlyProfitResource::collection(
            FeatureHourlyProfit::whereBelongsTo(request()->user())->simplePaginate(10)
        );
    }

    public function getProfitsByApplication(Request $request)
    {
        $request->validate(['karbari' => 'required|in:m,t,a']);

        $user = $request->user();

        $time = $user->variables->withdraw_profit * 86400;
        $amount = 0;

        FeatureHourlyProfit::whereBelongsTo($user)->with('feature', 'feature.properties')
            ->chunkById(100, function ($profits) use ($request, $user, $time, &$amount) {
                foreach ($profits as $profit) {
                    if ($profit->feature->properties->karbari == $request->karbari) {
                        $amount += $profit->amount;
                        $user->assets->increment($profit->asset, $profit->amount);


                        $profit->update([
                            'amount'     => 0,
                            'dead_line'  => now()->addSeconds($time)
                        ]);
                    }
                }
            });

        $user->notify(new FeatureHourlyProfitDeposit([
            'asset'   => match ($request->karbari) {
                'm' => 'red',
                't' => 'yellow',
                'a' => 'blue',
            },
            'amount'  => $amount,
            'karbari' => match ($request->karbari) {
                'm' => 'مسکونی',
                't' => 'تجاری',
                'a' => 'آموزشی',
            },
            'id' => null,
        ]));

        return response()->json([], 200);
    }

    /**
     * Get a single hourly profit and process it.
     * @param  FeatureHourlyProfit $featureHourlyProfit
     * @return HourlyProfitResource
     */
    public function getSingleProfit(FeatureHourlyProfit $featureHourlyProfit)
    {
        $feature = $featureHourlyProfit->feature;
        $user = request()->user();

        $user->assets->increment($featureHourlyProfit->asset, $featureHourlyProfit->amount);
        $time = $user->variables->withdraw_profit * 86400;

        if ($featureHourlyProfit->amount > 0) {

            $user->notify(new FeatureHourlyProfitDeposit([
                'asset'   => $feature->getColor(),
                'amount'  => $featureHourlyProfit->amount,
                'id'      => $feature->properties->id,
            ]));
        }

        $featureHourlyProfit->update([
            'amount'     => 0,
            'dead_line'  => now()->addSeconds($time)
        ]);

        return new HourlyProfitResource($featureHourlyProfit);
    }
}
