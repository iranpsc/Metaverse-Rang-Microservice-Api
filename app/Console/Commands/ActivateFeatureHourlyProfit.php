<?php

namespace App\Console\Commands;

use App\Models\Feature\Building;
use Illuminate\Console\Command;

class ActivateFeatureHourlyProfit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:activate-feature-hourly-profit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate feature hourly profit';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Building::where('construction_end_date', '<', now())->with('feature:id')->chunck(100, function ($buildings) {
            foreach ($buildings as $building) {
                $feature = $building->feature;

                $feature->load('dynasty:id,feature_id', 'hourlyProfit:id,feature_id,is_active');

                if ($feature->dynasty->count() > 0) {
                    $feature->hourlyProfit->update(['is_active' => true]);
                }
            }
        });
    }
}
