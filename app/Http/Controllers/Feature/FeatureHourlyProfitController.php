<?php

namespace App\Http\Controllers\Feature;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\HourlyProfitResource;
use App\Models\Feature;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\User;
use App\Notifications\FeatureHourlyProfitDeposit;

class FeatureHourlyProfitController extends Controller
{
    public function index()
    {
        return HourlyProfitResource::collection(FeatureHourlyProfit::whereBelongsTo(request()->user())->get());
    }

    public function getProfits(Request $request)
    {
        $request->validate(['karbari' => 'required|in:m,t,a']);

        $user = $request->user();
        if($user->features->isEmpty()) abort(404, 'کاربر ملکی ندارد');
        $time = $user->variables->withdraw_profit * 86400;

        FeatureHourlyProfit::whereBelongsTo($user)->with('feature')
        ->chunkById(100, function($profits)use($request, $user, $time) {
                foreach($profits as $profit) {
                    $feature = $profit->feature;
                    if ($profit->feature->properties->karbari == $request->karbari) {
                        $user->assets->increment($profit->asset, $profit->amount);
                        $user->notify(new FeatureHourlyProfitDeposit([
                            'asset' => $profit->asset,
                            'amount' => $profit->amount,
                            'feature_properties_id' => $feature->properties->id,
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

    public function getProfit(User $user, Feature $feature)
    {
        $profit = $feature->hourlyProfit;
        if (!$profit) {
            abort(404, 'سودی برای این ملک یافت نشد');
        }
        $user->assets->increment($profit->asset, $profit->amount);
        $time = $user->variables->withdraw_profit * 86400;
        $user->notify(new FeatureHourlyProfitDeposit([
            'asset' => $profit->asset,
            'amount' => $profit->amount,
            'feature_properties_id' => $feature->properties->id,
        ]));
        $profit->update([
            'amount' => 0,
            'dead_line' => now()->addSeconds($time)
        ]);
        return new HourlyProfitResource($profit);
    }
}
