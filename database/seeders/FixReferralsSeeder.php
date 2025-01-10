<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FixReferralsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::whereNotNull('referrer_id')->update(['referrer_id' => null]);

        User::with('referrals')->chunk(100, function ($users) {
            foreach ($users as $user) {
                foreach ($user->referrals as $referral) {
                    $referral->update(['referrer_id' => $user->id]);
                }
            }
        });
    }
}
