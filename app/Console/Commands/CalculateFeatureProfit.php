<?php

namespace App\Console\Commands;

use App\Events\UserStatusChanged;
use App\Models\Feature;
use Illuminate\Console\Command;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\User;

class CalculateFeatureProfit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:featureHourlyProfit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Caculates each feature's profit every 3 hours";

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        foreach (FeatureHourlyProfit::lazy() as $profit) {
            if (now() < $profit->dead_line) {
                if ($profit->updated_at->diffInHours(now()) >= 3) {
                    $feature = Feature::with('properties')->where('id', $profit->feature_id)->first();
                    $profit->increment('amount', $feature->properties->stability * 0.000041666);
                }
            }
        }

        User::where('last_seen', '<', now()->subMinutes(2))
        ->select(['id', 'code'])
        ->chunkById(100, function ($users) {
            foreach ($users as $user) {
                broadcast(new UserStatusChanged([
                    'code' => $user->code,
                    'status' => 'offline'
                ]));
            }
        });
    }
}
