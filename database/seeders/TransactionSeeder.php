<?php

namespace Database\Seeders;

use App\Models\Transaction;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $transactions = Transaction::all();

        $transactions->map(function($transaction) {
            $transaction->update(['id' => $this->generateId()]);
        });
    }

    private function generateId(): string
    {
        $id = 'TR-'.$this->randomNumber();
        while(Transaction::where('id', $id)->exists()) {
            $id = 'TR-' . $this->randomNumber();
        }
        return $id;
    }

    private function randomNumber()
    {
        return random_int(1000000, 9999999);
    }
}
