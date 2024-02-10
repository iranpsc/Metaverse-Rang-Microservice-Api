<?php

namespace App\Http\Controllers\Api\V2\Feature;

use App\Http\Controllers\Controller;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BuildFeatureController extends Controller
{
    public function getBuildPackage(Feature $feature)
    {
        $feature->load('properties:id,feature_id,area,density,karbari');

        $query = http_build_query([
            'feature_id' => $feature->id,
            'area' => $feature->properties->area,
            'density' => $feature->properties->density,
            'karbari' => $feature->properties->karbari,
        ]);

        $response = Http::get(config('app.three_d_meta_url') . '/api/v1/build-package', $query);

        if ($response->failed()) {
            return response()->json(
                ['message' => 'Failed to get build package'],
                $response->status()
            );
        }

        return $response->json();
    }
}
