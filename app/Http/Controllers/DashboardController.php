<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Morilog\Jalali\Jalalian;

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

        if(is_null($user->latestTransaction))
        {
            return response()->json([
                'error' => 'تراکنشی برای کاربر ثبت نشده است.'
            ], 200);
        }
        return response()->json([
            'payment-info' => [
                'ref_id' => $user->latestPayment->ref_id,
                'date' => Jalalian::forge($user->latestPayment->created_at)->format('Y/m/d'),
                'hour' => Jalalian::forge($user->latestPayment->created_at)->format('H:m:s'),
                'product' => $user->latestOrder->asset,
                'count' => $user->latestOrder->amount,
                'amount' => $user->latestTransaction->amount,
                'status' => $user->latestTransaction->status,
            ]
        ], 200);
    }
}
