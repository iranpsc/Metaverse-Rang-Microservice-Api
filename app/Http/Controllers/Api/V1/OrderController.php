<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuyAssetRequest;
use App\Models\Order;
use App\Models\User;
use App\Models\Variable;
use App\Notifications\TransactionNotification;
use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Store a new order for buying an asset.
     *
     * @param BuyAssetRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(BuyAssetRequest $request): JsonResponse
    {
        $this->authorize('buyFromStore', User::class);

        $rate = Variable::getRate($request->asset);

        $user = $request->user();

        // Create a new order
        $order = Order::create([
            'user_id' => $user->id,
            'asset' => $request->asset,
            'amount' => $request->amount,
        ]);

        // Create a new transaction for the order
        $transaction = $order->transaction()->create([
            'user_id' => $user->id,
            'asset' => $order->asset,
            'amount' => $order->amount,
            'action' => 'deposit',
        ]);

        $merchantId = $order->asset !== 'irr'
            ? config('parsian.merchant_id')
            : config('parsian.loan_account_merchant_id');

        // Send a request to Parsian for payment
        $response = parsian()
            ->orderId($order->id)
            ->amount($order->amount * $rate)
            ->merchantId($merchantId)
            ->request()
            ->callbackUrl(route('parsian.callback'))
            ->send();

        if (!$response->success()) {
            throw ValidationException::withMessages([
                'error' => $response->error()->message()
            ]);
        }

        $transaction->update(['token' => $response->token()]);

        return response()->json([
            'link' => $response->url(),
        ]);
    }

    /**
     * Handle the callback after a payment is made.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Request $request): RedirectResponse
    {
        $params = http_build_query($request->all());

        $order = Order::where('id', $request->OrderId)->with('user', 'transaction')->firstOrFail();
        $transaction = $order->transaction;

        // If status = 0, transaction is successful
        if ($request->status == 0) {

            $amount = $order->amount * Variable::getRate($order->asset);

            $merchantId = $order->asset !== 'irr'
                ? config('parsian.merchant_id')
                : config('parsian.loan_account_merchant_id');

            $response = parsian()
                ->token($transaction->token)
                ->merchantId($merchantId)
                ->verification()
                ->send();

            if ($response->success()) {
                $order->update(['status' => $response->status()]);

                $transaction->update([
                    'status' => $response->status(),
                    'ref_id' => $response->referenceId()
                ]);

                $user = $order->user;

                if ($user->can('canGetBonus', $order)) {

                    $user->firstOrder()->create([
                        'type' => $order->asset,
                        'amount' => $order->amount,
                        'date' => jdate(now())->format('Y/m/d'),
                        'bonus' => $order->amount * 0.5,
                    ]);

                    $bonus = $order->amount * 0.5;
                    // Increase the user's asset amount with the order amount and bonus
                    $user->wallet->increment($order->asset, $order->amount + $bonus);
                } else {
                    // Increase the user's asset amount with the order amount
                    $user->wallet->increment($order->asset, $order->amount);
                }

                $order->payment()->create([
                    'user_id' => $user->id,
                    'ref_id' => $response->referenceId(),
                    'card_pan' => $response->cardHash() ?? 'card-hash',
                    'gateway' => 'parsian',
                    'amount' => $amount,
                    'product' => $order->asset
                ]);

                // Check if the order asset is not IRR
                if ($order->asset !== 'irr') {
                    ReferralService::referral($user, $order);
                }

                $user->notify(new TransactionNotification($order));
                $user->deposit();
            }
        } else {
            $order->update([
                'status' => $request->status
            ]);
            $transaction->update([
                'status' => $request->status
            ]);
        }

        return redirect()->to('https://rgb.irpsc.com/metaverse/payment/verify?' . $params);
    }
}
