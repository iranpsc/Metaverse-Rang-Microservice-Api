<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePrivacyRequest;
use App\Http\Resources\LatestTransactionResource;
use App\Http\Resources\PrivacyResource;
use App\Http\Resources\TransactionResource;
use App\Http\Resources\UserResource;
use App\Models\Privacy;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $user->token = $request->bearerToken();
        return new UserResource($request->user());
    }

    public function getUserLatestTransaction(Request $request)
    {
        $user = User::where('id', $request->user()->id)
            ->with(['latestPayment', 'latestTransaction', 'latestOrder'])
            ->first();
        return new LatestTransactionResource($user);
    }

    public function transactions(Request $request)
    {
        return TransactionResource::collection(Transaction::whereBelongsTo($request->user())->simplePaginate(10));
    }

    public function getPrivacySettings(Request $request)
    {
        return new PrivacyResource($request->user()->privacy);
    }

    public function updatePrivacySettings(UpdatePrivacyRequest $request)
    {
        Privacy::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'name' => $request->setting,
            ],

            [
                'display' => $request->value
            ]
        );
        return response()->noContent(200);
    }
}
