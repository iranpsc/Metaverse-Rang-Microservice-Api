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
        // Get all hourly profits for the current user and return them as a collection
        return HourlyProfitResource::collection(FeatureHourlyProfit::whereBelongsTo(request()->user())->get());
    }

    public function getProfitsByApplication(Request $request)
    {
        // Validate the request parameter
        $request->validate(['karbari' => 'required|in:m,t,a']);

        $user = $request->user();

        // Calculate the time based on the user's withdraw_profit variable
        $time = $user->variables->withdraw_profit * 86400;

        // Retrieve hourly profits in chunks and process them
        FeatureHourlyProfit::whereBelongsTo($user)->with('feature', 'feature.properties')
            ->chunkById(100, function ($profits) use ($request, $user, $time) {
                foreach ($profits as $profit) {
                    // Check if the profit's feature property matches the requested karbari
                    if ($profit->feature->properties->karbari == $request->karbari) {
                        // Increment the user's assets based on the profit's asset and amount
                        $user->assets->increment($profit->asset, $profit->amount);
                        // Notify the user about the hourly profit deposit
                        $user->notify(new FeatureHourlyProfitDeposit([
                            'asset'   => $profit->asset,
                            'amount'  => $profit->amount,
                            'id'      => $profit->feature->properties->id,
                        ]));
                        // Update the profit's amount and deadline
                        $profit->update([
                            'amount'     => 0,
                            'dead_line'  => now()->addSeconds($time)
                        ]);
                    }
                }
            });

        // Get all hourly profits for the current user and return them as a collection
        return HourlyProfitResource::collection(FeatureHourlyProfit::whereBelongsTo(request()->user())->get());
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

        // Increment the user's assets based on the profit's asset and amount
        $user->assets->increment($featureHourlyProfit->asset, $featureHourlyProfit->amount);
        $time = $user->variables->withdraw_profit * 86400;

        if ($featureHourlyProfit->amount > 0) {
            // Notify the user about the hourly profit deposit
            $user->notify(new FeatureHourlyProfitDeposit([
                'asset'   => $feature->getColor(),
                'amount'  => $featureHourlyProfit->amount,
                'id'      => $feature->properties->id,
            ]));
        }

        // Update the profit's amount and deadline
        $featureHourlyProfit->update([
            'amount'     => 0,
            'dead_line'  => now()->addSeconds($time)
        ]);

        // Return the updated hourly profit as a resource
        return new HourlyProfitResource($featureHourlyProfit);
    }
}
