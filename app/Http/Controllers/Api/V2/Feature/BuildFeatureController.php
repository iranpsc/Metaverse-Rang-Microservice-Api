<?php

namespace App\Http\Controllers\Api\V2\Feature;

use App\Http\Controllers\Controller;
use App\Http\Requests\StartBuildingFeatureRequest;
use App\Http\Requests\UpdateBuildingFeatureRequest;
use App\Http\Resources\V2\BuildingModelResource;
use App\Models\Feature;
use Illuminate\Http\Request;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Http;
use App\Models\Feature\BuildingModel;
use App\Models\Feature\FeatureHourlyProfit;
use App\Models\IsicCode;
use Illuminate\Support\Facades\DB;

class BuildFeatureController extends Controller
{
    public function getBuildPackage(Feature $feature)
    {
        $feature->load('properties:id,feature_id,area,density,karbari', 'owner:id', 'coordinates');

        throw_unless($feature->owner->id === auth()->id(), AuthorizationException::class);

        $query = http_build_query([
            'feature_id' => $feature->id,
            'area' => $feature->properties->area,
            'density' => $feature->properties->density,
            'karbari' => $feature->properties->karbari,
            'page' => request('page', 1),
        ]);

        $url = config('app.three_d_meta_url') . '/api/v1/build-package';

        $response = $this->sendRequest($url, $query);

        $response = $this->calculateRequiredSatisfaction($feature, $response);
        $response = $this->mergeCoordinates($feature, $response);

        $this->updateOrCreateModels($response['data']);

        return response()->json($response);
    }

    private function calculateRequiredSatisfaction(Feature $feature, array $data)
    {
        foreach ($data['data'] as &$item) {
            $attributes = $item['attributes'];

            $area = collect($attributes)->firstWhere('slug', 'area')['value'];
            $density = collect($attributes)->firstWhere('slug', 'density')['value'];

            $item['required_satisfaction'] = number_format($area * $feature->getKarbariCoefficient() * $density * 0.1 / 100, 4);
        }

        return $data;
    }

    public function buildFeature(StartBuildingFeatureRequest $request, Feature $feature, BuildingModel $buildingModel)
    {
        $this->authorize('build', [$feature, $buildingModel]);

        $constructionLengthHours = $buildingModel->required_satisfaction * 288000 / $request->launched_satisfaction;

        $constructionEndDate = $this->getConstructionEndDate($constructionLengthHours);

        if ($request->filled('activity_line')) {

            IsicCode::firstOrCreate(
                ['name' => trim($request->activity_line)],
                ['name' => trim($request->activity_line)]
            );

            $information = $request->only([
                'activity_line',
                'name',
                'address',
                'postal_code',
                'website',
                'description'
            ]);
        }

        $feature->buildingModels()->attach($buildingModel, [
            'construction_start_date' => now(),
            'construction_end_date' => $constructionEndDate,
            'launched_satisfaction' => $request->launched_satisfaction,
            'information' => $information ?? null,
            'rotation' => $request->rotation,
            'position' => $request->position,
        ]);

        // Deactivate all hourly profits for this feature
        FeatureHourlyProfit::where('feature_id', $feature->id)->update(['is_active' => false]);

        // Calculate bubble diameter
        $bubbleDiameter = $this->calculateBubbleDiameter($buildingModel);

        $feature->buildingModels()->updateExistingPivot($buildingModel, [
            'bubble_diameter' => $bubbleDiameter,
        ]);

        return response()->json([], 200);
    }

    public function getBuildings(Feature $feature)
    {
        $feature->load(['buildingModels' => function ($query) {
            $query->withPivot([
                'construction_start_date',
                'construction_end_date',
                'launched_satisfaction',
                'information',
                'rotation',
                'position',
                'bubble_diameter',
            ]);
        }]);

        return BuildingModelResource::collection($feature->buildingModels);
    }

    public function updateBuilding(UpdateBuildingFeatureRequest $request, Feature $feature, BuildingModel $buildingModel)
    {
        $this->authorize('build', [$feature, $buildingModel]);

        $constructionLengthHours = $buildingModel->required_satisfaction * 288000 / $request->launched_satisfaction;

        $constructionEndDate = $this->getConstructionEndDate($constructionLengthHours);

        if ($request->filled('activity_line')) {

            IsicCode::firstOrCreate(
                ['name' => trim($request->activity_line)],
                ['name' => trim($request->activity_line)]
            );

            $information = $request->only([
                'activity_line',
                'name',
                'address',
                'postal_code',
                'website',
                'description'
            ]);
        }

        $feature->buildingModels()->updateExistingPivot($buildingModel, [
            'construction_start_date' => now(),
            'construction_end_date' => $constructionEndDate,
            'launched_satisfaction' => $request->launched_satisfaction,
            'information' => $information ?? null,
            'rotation' => $request->rotation,
            'position' => $request->position,
        ]);

        return response()->json([], 200);
    }

    private function updateOrCreateModels(array $data): void
    {
        $models = [];
        foreach ($data as $item) {
            $models[] = [
                'model_id' => $item['id'],
                'name' => $item['name'],
                'sku' => $item['sku'],
                'images' => json_encode($item['images']),
                'attributes' => json_encode($item['attributes']),
                'file' => json_encode($item['file']),
                'required_satisfaction' => $item['required_satisfaction'],
            ];
        }

        DB::transaction(function () use ($models) {
            BuildingModel::upsert($models, ['model_id'], [
                'name',
                'sku',
                'images',
                'attributes',
                'file',
                'required_satisfaction',
            ]);
        });
    }

    private function sendRequest(string $url, $query = null)
    {
        try {
            $response = Http::get($url, $query);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error in sending request to 3D Meta API.',
                'error' => $e->getMessage(),
            ], $e->getCode());
        }

        return $response->json();
    }

    private function mergeCoordinates(Feature $feature, array $response)
    {
        $coordinates = $feature->coordinates->map(function ($coordinate) {
            return $coordinate->implodeXY();
        });

        $response['feature']['coordinates'] = $coordinates;

        return $response;
    }

    private function getConstructionEndDate($constructionLengthHours)
    {
        $endTime = $this->calculateEndTime($constructionLengthHours);

        $days = $endTime['days'];
        $hours = $endTime['hours'];
        $minutes = $endTime['minutes'];
        $seconds = $endTime['seconds'];

        return now()->addDays($days)->addHours($hours)->addMinutes($minutes)->addSeconds($seconds);
    }

    private function calculateEndTime($hours): array
    {

        // Convert to total seconds
        $seconds = $hours * 3600;

        // Calculate each unit
        $days = floor($seconds / 86400);
        $seconds -= $days * 86400;

        $hours = floor($seconds / 3600);
        $seconds -= $hours * 3600;

        $minutes = floor($seconds / 60);
        $seconds -= $minutes * 60;

        return array(
            "days" => $days,
            "hours" => $hours,
            "minutes" => $minutes,
            "seconds" => round($seconds)
        );
    }

    /**
     * Calculate bubble diameter
     *
     * @param BuildingModel $buildingModel
     * @return float
     */
    private function calculateBubbleDiameter(BuildingModel $buildingModel)
    {
        $attributes = collect($buildingModel->attributes);
        $width = $attributes->firstWhere('slug', 'width')['value'];
        $length = $attributes->firstWhere('slug', 'length')['value'];
        $perimeter = 2 * ($width + $length);
        $density = $attributes->firstWhere('slug', 'density')['value'];

        $coefficient = 1;

        for ($i = 0; $i < $density - 1; $i++) {
            $coefficient += 0.3;
        }

        return $perimeter * $coefficient;
    }
}
