<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\BuyAssetRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Models\Variable;
use App\Notifications\TransactionNotification;
use App\Services\ReferalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali\Jalalian;

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
            'status' => 0
        ]);

        // Send a request to ZarinPal for payment
        $response = Http::post(config('zarinpal.curl.post'), [
            "merchant_id" => config('zarinpal.merchant_id'),
            "amount" => $order->amount * $rate,
            "callback_url" => route('order.callback', $order->id),
            "description" => "خرید تست",
            "metadata" => ["email" => $user->email],
        ]);

        if ($response->successful()) {
            $result = $response->json();
            if ($result['data']['code'] == 100) {
                return response()->json([
                    'link' => 'https://www.zarinpal.com/pg/StartPay/' . $result['data']["authority"],
                ]);
            } else {
                // Update the order and transaction status to -1 (error)
                $order->update(['status' => -1]);
                $transaction->update(['status' => -1]);
                return response()->json([
                    'error' => $response->serverError()
                ]);
            }
        } else {
            // Update the order and transaction status to -1 (error)
            $order->update(['status' => -1]);
            $transaction->update(['status' => -1]);
            return response()->json([
                'error' => $response->serverError()
            ]);
        }
    }

    /**
     * Handle the callback after a payment is made.
     *
     * @param Order $order
     * @return \Illuminate\Http\RedirectResponse
     */
    public function callback(Order $order): RedirectResponse
    {
        $transaction = $order->transaction;
        $amount = $order->amount * Variable::getRate($order->asset);

        // Verify the payment with ZarinPal
        $response = Http::post(config('zarinpal.curl.verify'), [
            "merchant_id" => config('zarinpal.merchant_id'),
            "authority" => $_GET['Authority'],
            "amount" => $amount,
        ]);

        $result = $response->json();

        if ($response->successful()) {
            if ($result['data']['code'] == 100) {
                // Update the transaction and order status to 1 (success)
                $transaction->update(['status' => 1]);
                $order->update(['status' => 1]);
                $user = $order->user;

                // Check if the user can get a bonus for the order
                if ($user->can('canGetBonus', $order)) {
                    // Create a new first order record with bonus
                    $user->firstOrder()->create([
                        'type' => $order->asset,
                        'amount' => $order->amount,
                        'date' => Jalalian::now()->format('Y-m-d'),
                        'bonus' => $order->amount * 0.5,
                    ]);
                    $bonus = $order->amount * 0.5;
                    // Increase the user's asset amount with the order amount and bonus
                    $user->wallet->increment($order->asset, $order->amount + $bonus);
                } else {
                    // Increase the user's asset amount with the order amount
                    $user->wallet->increment($order->asset, $order->amount);
                }

                // Create a payment record
                Payment::create([
                    'user_id' => $user->id,
                    'ref_id' => $result['data']['ref_id'],
                    'card_pan' => $result['data']['card_pan'],
                    'gateway' => 'زرین پال',
                    'amount' => $order->amount * Variable::getRate($order->asset),
                    'product' => $order->asset
                ]);

                // Check if the order asset is not IRR
                if($order->asset !== 'irr') {
                    // Perform referral actions
                    ReferalService::referal($user, $order);
                }

                // Notify the user about the transaction
                $user->notify(new TransactionNotification($order));
                $user->deposit();
                return redirect()->to('https://rgb.irpsc.com/metaverse/payment/verify');
            }
        } else {
            if ($result['errors']['code'] == -51) {
                // Update the transaction and order status to -1 (error)
                $transaction->update(['status' => -1]);
                $order->update(['status' => -1]);
                return redirect()->to('https://rgb.irpsc.com/metaverse/payment/verify');
            }
        }
    }
}
