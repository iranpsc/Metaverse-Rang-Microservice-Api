<?php

namespace App\Repositories;

use App\Models\Coordinate;
use App\Models\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FeatureRepository extends Repository
{
    /**
     * Get features by their IDs.
     *
     * @param array $ids
     * @return Collection
     */
    public function getByIds(array $ids): Collection
    {
        return Feature::find($ids);
    }

    /**
     * Get a single feature by its ID.
     *
     * @param mixed $id
     * @return Model
     */
    public function getOneById($id): Model
    {
        return Feature::find($id);
    }

    /**
     * Get all features based on the given request.
     *
     * @param Request $request
     * @return mixed
     */
    public function all(Request $request)
    {
        $request->validate([
            'points' => 'required|array|min:4',
            'points.*' => 'required|regex:/^([0-9]+(\.[0-9]+)?,[0-9]+(\.[0-9]+)?)$/',
            'load_buildings' => 'nullable|boolean',
            'auth_user_features' => 'nullable|boolean',
        ]);

        $points = array_map(function ($point) {
            return explode(',', $point);
        }, $request->points);

        $existingGeometries = Coordinate::whereBetween('x', [
            $points[0][0],
            $points[1][0]
        ])->whereBetween('y', [
            $points[0][1],
            $points[2][1]
        ])->distinct('geometry_id')
            ->pluck('geometry_id');

        $featuresQuery = Feature::whereIn('id', $existingGeometries)
            ->selectRaw('id, owner_id as owner')
            ->with([
                'properties:id,feature_id,rgb',
                'geometry.coordinates:id,geometry_id,x,y'
            ]);

        if ($request->boolean('load_buildings')) {
            $featuresQuery->with(['buildingModels' => function ($query) {
                $query->select('building_models.id', 'building_models.model_id', 'building_models.file')
                    ->withPivot('construction_start_date', 'construction_end_date', 'rotation', 'position');
            }]);
        }

        return $featuresQuery->get();
    }
}
