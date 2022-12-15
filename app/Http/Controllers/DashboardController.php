<?php

namespace App\Http\Controllers;

use App\Helpers\AssetHelper;
use App\Http\Requests\UpdatePrivacyRequest;
use App\Http\Resources\LatestTransactionResource;
use App\Http\Resources\PrivacyResource;
use App\Http\Resources\UserResource;
use App\Models\Privacy;
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

        if (is_null($user->latestTransaction)) {
            return response()->json([
                'error' => 'تراکنشی برای کاربر ثبت نشده است.'
            ], 404);
        }

        return new LatestTransactionResource($user);
    }

    public function transactions(Request $request)
    {
        $user = $request->user();
        $transactions = $user->transactions;
        if (is_null($transactions)) {
            return response()->json([
                'error' => 'تراکنشی برای کاربر ثبت نشده است.'
            ], 404);
        } else {
            return response()->json([$transactions->map(function ($transaction) {
                return [
                    'id'     => $transaction->id,
                    'type'   => getTransactionTitle($transaction),
                    'asset'  => AssetHelper::getAssetTitle($transaction->asset),
                    'amount' => $transaction->amount,
                    'action' => $transaction->action === 'withdraw' ? 'برداشت' : 'واریز',
                    'status' => getTransactionStatus($transaction)
                ];
            })], 200);
        }
    }

    public function getPrivacySettings(Request $request){
        return new PrivacyResource($request->user()->privacy);
    }

    public function updatePrivacySettings(UpdatePrivacyRequest $request) {
        Privacy::updateOrCreate(
            [
                'user_id' => $request->user()->id ,
                'name' => $request->setting,
            ],

            [
                'display' => $request->value
            ]
        );
        return response()->json(['message' => 'تنظیمات بروزرسانی شد!'], 200);
    }
}
