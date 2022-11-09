<?php

namespace App\Console\Commands;

use App\Models\Feature;
use Illuminate\Console\Command;
use App\Models\Feature\FeatureHourlyProfit;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
        foreach (FeatureHourlyProfit::lazy() as $profit)
        {
            if (now() < $profit->dead_line)
            {
                if($profit->updated_at->diffInHours(now()) >= 3) {
                    $feature = Feature::with('properties')->where('id', $profit->feature_id)->first();
                    $profit->increment('amount', $feature->properties->stability * 0.000041666);
                }
            }
        }
    }
}
