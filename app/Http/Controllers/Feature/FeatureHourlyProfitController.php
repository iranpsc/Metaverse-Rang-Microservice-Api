<?php

namespace App\Http\Controllers\Feature;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\User;

class FeatureHourlyProfitController extends Controller
{
    public function getHourlyProfits(Request $request, string $karbari = null)
    {
        if($request->user()->features->isEmpty()) abort(404, 'کاربر ملکی ندارد');
        if (!$karbari)
            abort(404, 'کاربری ملک را تعیین کنید');

        foreach ($request->user()->featureProfits as $profit) {
            $feature = Feature::with('properties')->where('id', $profit->feature->id)->first();
            if ($feature->properties->karbari != $karbari) continue;
            $request->user()->assets->increment($profit->asset, $profit->amount);
            $time = $request->user()->variables->withdraw_profit * 86400;
            $profit->update([
                'amount' => 0,
                'dead_line' => now()->addSeconds($time)
            ]);
        }
        return response()->json(['success' => 'سود ملک ها جمع آوری شدند']);
    }

    public function getHourlyProfit(User $user, Feature $feature)
    {
        $profit = $feature->hourlyProfit;
        if (!$profit) {
            abort(404, 'سودی برای این ملک یافت نشد');
        }
        $user->assets->increment($profit->asset, $profit->amount);
        $time = request()->user()->variables->withdraw_profit * 86400;
        $profit->update([
            'amount' => 0,
            'dead_line' => now()->addSeconds($time)
        ]);
        return response()->json([
            'success' => 'سود این ملک جمع آوری شد'
        ]);
    }
}
