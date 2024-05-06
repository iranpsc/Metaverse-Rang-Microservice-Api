<?php

namespace App\Console\Commands;

use App\Events\UserStatusChanged;
use Illuminate\Console\Command;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\FeatureProperties;
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
        FeatureHourlyProfit::where('dead_line', '>', now())
            ->where('updated_at', '<', now()->subHours(3))
            ->where('is_active', true)
            ->select(['id', 'feature_id', 'amount'])
            ->chunkById(100, function ($profits) {
                foreach ($profits as $profit) {
                    $stability = FeatureProperties::where('feature_id', $profit->feature_id)->pluck('stability')->first();
                    $profit->increment('amount', $stability * 0.000041666);
                }
            });

        User::whereBetween('last_seen', [now()->subMinutes(3), now()->subMinutes(2)])
            ->select('id')
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    broadcast(new UserStatusChanged([
                        'id'     => $user->id,
                        'online' => false
                    ]));
                }
            });
    }
}
