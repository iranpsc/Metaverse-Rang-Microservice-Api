<?php

namespace Database\Seeders;

use App\Models\FeatureProperties;
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
        $features = FeatureProperties::query();

        $features->lazyById()->map(function($feature) {
            $feature->update([
                'stability' => $feature->area * $feature->density,
            ]);
        });
    }
}
