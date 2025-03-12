<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FeatureCenterCalculatorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Feature::with('properties', 'coordinates')->chunk(100, function ($features) {
            foreach ($features as $feature) {
                // Calculate the center of the feature based on coordinates
                $coordinates = $feature->coordinates->map(function ($coordinate) {
                    return ['x' => $coordinate->x, 'y' => $coordinate->y];
                })->toArray();

                $center = calculatePolygonCentroid($coordinates);

                $feature->properties->update([
                    'center' => $center
                ]);
            }
        });
    }
}
