<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuyAssetRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Variable;
use App\Notifications\TransactionNotification;
use App\Services\ReferalService;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali\Jalalian;

class OrderController extends Controller
{
    public function create(BuyAssetRequest $request)
    {
        $rate = Variable::getRate($request->asset);

        $user = $request->user();

        $order = Order::create([
            'user_id' => $user->id,
            'asset' => $request->asset,
            'amount' => $request->amount,
        ]);

        $transaction = $order->transaction()->create([
            'user_id' => $user->id,
            'asset' => $order->asset,
            'amount' => $order->amount,
            'action' => 'deposit',
            'status' => 0
        ]);

        $next_transaction = $order->transaction()->create([
            'user_id' => $user->id,
            'asset' => 'irr',
            'amount' => $order->amount * $rate,
            'action' => 'deposit',
            'status' => 0
        ]);

        $response = Http::post(config('rgb.curl.post'), [
            "merchant_id" => env('ZARINPAL_MERCHANT_ID'),
            "amount" => $order->amount * $rate,
            "callback_url" => route('order.callback', $order->id),
            "description" => "خرید تست",
            "metadata" => ["email" => $user->email],
        ]);

        if($response->successful()) {
            $result = $response->json();
            if($result['data']['code'] == 100) {
                return response()->json([
                    'link' => 'https://www.zarinpal.com/pg/StartPay/' . $result['data']["authority"],
                ]);
            } else {
                $order->update(['status' => -1]);
                $transaction->update(['status' => -1]);
                $next_transaction->update(['status' => -1]);
                return response()->json([
                    'error' => $response->serverError()
                ]);
            }
        } else {
            $order->update(['status' => -1]);
            $transaction->update(['status' => -1]);
            return response()->json([
                'error' => $response->serverError()
            ]);
        }
    }

    public function callback(Order $order)
    {
        $transaction = $order->transaction;
        $next_transaction = Transaction::findOrFail($transaction->id + 1);

        $response = Http::post(config('rgb.curl.verify'), [
            "merchant_id" => env('ZARINPAL_MERCHANT_ID'),
            "authority" => $_GET['Authority'],
            "amount" => $next_transaction->amount,
        ]);

        $result = $response->json();

        if($response->successful()) {
            if($result['data']['code'] == 100) {
                $transaction->update(['status' => 1]);
                $next_transaction->update(['status' => 1]);
                $order->update(['status' => 1]);
                $user = $order->user;

                if ($user->can('canGetBonus', $order)) {
                    $user->firstOrder()->create([
                        'type' => $order->asset,
                        'amount' => $order->amount,
                        'date' => Jalalian::now()->format('Y-m-d'),
                        'bonus' => $order->amount * 0.5,
                    ]);
                    $bonus = $order->amount * 0.5;
                    $user->assets->increment($order->asset, $order->amount + $bonus);
                } else {
                    $user->assets->increment($order->asset, $order->amount);
                }

                $payment = Payment::create([
                    'user_id' => $user->id,
                    'ref_id' => $result['data']['ref_id'],
                    'card_pan' => $result['data']['card_pan'],
                    'gateway' => 'زرین پال',
                    'amount' => $next_transaction->amount,
                    'product' => $order->asset
                ]);

                ReferalService::referal($user, $order);

                $user->notify(new TransactionNotification($order));
                $user->deposit();
                return redirect()->to(env('FRONT_URL').'/payment/verify');
            }
        } else {
            if($result['errors']['code'] == -51) {
                $transaction->update(['status' => -1]);
                $next_transaction->update(['status' => -1]);
                $order->update(['status' => -1]);
                return redirect()->to(env('FRONT_URL').'/payment/verify');
            }
        }
    }
}
