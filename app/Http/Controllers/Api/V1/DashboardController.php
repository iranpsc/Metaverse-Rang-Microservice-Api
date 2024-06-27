<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\LatestTransactionResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return ProfileResource
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $user->load(['wallet', 'latestProfilePhoto', 'level', 'features'])
            ->loadCount(['followers', 'following', 'notifications as unreadNotifications_count' => function ($query) {
                $query->where('read_at', null);
            }]);

        return new ProfileResource($user);
    }

    /**
     * Get the latest transaction of the current user.
     *
     * @param Request $request
     * @return LatestTransactionResource
     */
    public function latestTransaction(Request $request)
    {
        $user = $request->user()->load(['latestPayment', 'latestTransaction', 'latestOrder']);
        return new LatestTransactionResource($user);
    }

    /**
     * Get the transactions of the current user.
     *
     * @param Request $request
     * @return TransactionResource
     */
    public function transactions(Request $request)
    {
        return TransactionResource::collection(
            Transaction::whereBelongsTo($request->user())->simplePaginate(10)
        );
    }

    /**
     * Get the wallet of the current user.
     *
     * @param Request $request
     * @return WalletResource
     */
    public function showWallet(Request $request)
    {
        return new WalletResource($request->user()->wallet);
    }
}
