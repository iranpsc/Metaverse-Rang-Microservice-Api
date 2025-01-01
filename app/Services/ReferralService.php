<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Variable;

class ReferralService
{
    /**
     * Handle referral when an order is placed.
     *
     * @param User $user
     * @param Order $order
     * @return void
     */
    public static function referral(User $user, Order $order)
    {
        // Check if the user has a reference
        if ($user->has_reference()) {
            // If the asset is 'irr', do not proceed with referral
            if ($order->asset == 'irr') {
                return;
            }

            $psc_price = Variable::getRate('psc');
            $reference = $user->reference;

            // Calculate the total amount referred by the reference user
            $reference_amount = $reference->referalOrderHistories->sum('amount') * $psc_price ?? 0;

            // Calculate the referral amount for the referer based on the order asset
            if (in_array($order->asset, ['blue', 'red', 'yellow'])) {
                $referer_amount = (($order->amount * Variable::getRate($order->asset)) / $psc_price) * 0.5;
            } else {
                $referer_amount = $order->amount * 0.5;
            }

            $referalLimit = $reference->variables;

            // Check if the reference user has reached the referral profit limit
            if ($reference_amount >= $referalLimit->referral_profit) {
                return;
            }

            // Increment the referer's 'psc' asset with the referral amount
            $reference->wallet->increment('psc', $referer_amount);

            // Create a new referal order history entry
            $reference->referralOrderHistories()->create([
                'referrer_id' => $user->id,
                'amount' => $referer_amount,
            ]);
        }
    }
}
