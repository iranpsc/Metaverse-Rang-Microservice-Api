<?php

namespace App\Repositories;

use App\Models\Feature;

class FeatureRepository {
    private $features;

    public function __construct()
    {
        $this->features = Feature::with('properties')->get();
    }

    public function maskoni()
    {
        return $this->features->reject(function($feature) {
            return $feature->properties->karbari !== 'm';
        })->count();
    }

    public function tejari()
    {
        return $this->features->reject(function($feature) {
            return $feature->properties->karbari !== 't';
        })->count();
    }

    public function amoozeshi()
    {
        return $this->features->reject(function($feature) {
            return $feature->properties->karbari !== 'a';
        })->count();
    }
}
