<?php

namespace App\Http\Controllers;

use App\Http\Resources\LatestTransactionResource;
use App\Http\Resources\PublicProfile\PersonalInfo;
use App\Http\Resources\UserResource;
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

        if(is_null($user->latestTransaction))
        {
            return response()->json([
                'error' => 'تراکنشی برای کاربر ثبت نشده است.'
            ], 200);
        }

        return new LatestTransactionResource($user);
    }

    public function home(Request $request, string $code) {
        $user = User::with('kyc', 'customs', 'customs.passions', 'profilePhotos', 'level')
        ->where('code', $code)->first();
        if(!$user) {
            return response()->json(['error' => 'کاربر یافت نشد'], 404);
        }
        return new PersonalInfo($user);
    }
}
