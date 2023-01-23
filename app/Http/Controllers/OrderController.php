<?php

namespace App\Http\Controllers;

use App\Http\Requests\BuyAssetRequest;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Transaction;
use App\Models\Variable;
use App\Notifications\TransactionNotification;
use App\Services\ReferalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Http;
use Morilog\Jalali\Jalalian;

class OrderController extends Controller
{
    public function create(BuyAssetRequest $request): JsonResponse
    {
        $rate = Variable::getRate($request->asset);

        $user = $request->user();

        $order = Order::create([
            'user_id' => $user->id,
            'asset' => $request->asset,
            'amount' => $request->amount,
        ]);

        $transaction = $order->transaction()->create([
            'id' => $this->generateId(),
            'user_id' => $user->id,
            'asset' => $order->asset,
            'amount' => $order->amount,
            'action' => 'deposit',
            'status' => 0
        ]);

        $response = Http::post(config('rgb.curl.post'), [
            "merchant_id" => 'de84d0d6-d7b1-4a68-9ac6-125156d6a35d',
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
                $order->update(['status' => -1]);
                $transaction->update(['status' => -1]);
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

    public function callback(Order $order): RedirectResponse
    {
        $transaction = $order->transaction;
        $amount = $order->amount * Variable::getRate($order->asset);

        $response = Http::post(config('rgb.curl.verify'), [
            "merchant_id" => 'de84d0d6-d7b1-4a68-9ac6-125156d6a35d',
            "authority" => $_GET['Authority'],
            "amount" => $amount,
        ]);

        $result = $response->json();

        if ($response->successful()) {
            if ($result['data']['code'] == 100) {
                $transaction->update(['status' => 1]);
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

                Payment::create([
                    'user_id' => $user->id,
                    'ref_id' => $result['data']['ref_id'],
                    'card_pan' => $result['data']['card_pan'],
                    'gateway' => 'زرین پال',
                    'amount' => $order->amount * Variable::getRate($order->asset),
                    'product' => $order->asset
                ]);

                ReferalService::referal($user, $order);

                $user->notify(new TransactionNotification($order));
                $user->deposit();
                return redirect()->to('https://rgb.irpsc.com/metaverse/payment/verify');
            }
        } else {
            if ($result['errors']['code'] == -51) {
                $transaction->update(['status' => -1]);
                $order->update(['status' => -1]);
                return redirect()->to('https://rgb.irpsc.com/metaverse/payment/verify');
            }
        }
    }

    private function generateId(): string
    {
        $id = 'TR-' . $this->randomNumber();
        while (Transaction::where('id', $id)->exists()) {
            $id = 'TR-' . $this->randomNumber();
        }
        return $id;
    }

    private function randomNumber(): int
    {
        return random_int(1000000, 9999999);
    }
}
