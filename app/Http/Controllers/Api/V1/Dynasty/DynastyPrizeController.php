<?php

namespace App\Http\Controllers\Api\V1\Dynasty;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dynasty\DynastyPrizeResource;
use App\Models\Dynasty\RecievedPrize;
use App\Models\Variable;
use Illuminate\Http\Request;

class DynastyPrizeController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return DynastyPrizeResource
     */
    public function index()
    {
        return DynastyPrizeResource::collection(request()->user()->recievedDynastyPrizes);
    }

    /**
     * Display the specified recievedPrize.
     * @param RecievedPrize $recievedPrize
     * @return DynastyPrizeResource
     */
    public function show(RecievedPrize $recievedPrize)
    {
        return new DynastyPrizeResource($recievedPrize);
    }

    /**
     * Get the prize.
     * @param Request $request
     * @param RecievedPrize $recievedPrize
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, RecievedPrize $recievedPrize)
    {
        $user = $request->user();
        $prize = $recievedPrize->prize;

        $user->wallet->increment('psc', $prize->psc / Variable::getRate('psc'));
        $user->wallet->increment('satisfaction', $prize->satisfaction);

        $variables = $user->variables;

        $variables->update([
            'referral_profit' => $variables->referral_profit + ($variables->referral_profit * $prize->introduction_profit_increase),
            'data_storage' => $variables->data_storage + ($variables->data_storage * $prize->data_storage),
            'withdraw_profit' => $variables->withdraw_profit + ($variables->withdraw_profit * $prize->accumulated_capital_reserve),
        ]);

        $recievedPrize->delete();
        return response()->noContent();
    }
}
