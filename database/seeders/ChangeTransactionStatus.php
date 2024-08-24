<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ChangeTransactionStatus extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Transaction::chunkById(100, function ($transactions) {
            foreach ($transactions as $transaction) {
                // Unsuccessful transations
                if ($transaction->status === -1) {
                    $transaction->update(['status' => -138]);
                }

                // Successful transactions
                if ($transaction->status === 1) {
                    $transaction->update(['status' => 0]);
                }

                // Pending transactions
                if ($transaction->status === 0) {
                    $transaction->update(['status' => 1]);
                }
            }
        });

        Order::chunk(100, function ($orders) {
            foreach ($orders as $order) {
                // Unsuccessful orders
                if ($order->status === -1) {
                    $order->update(['status' => -138]);
                }

                // Successful orders
                if ($order->status === 1) {
                    $order->update(['status' => 0]);
                }

                // Pending orders
                if ($order->status === 0) {
                    $order->update(['status' => 1]);
                }
            }
        });
    }
}
