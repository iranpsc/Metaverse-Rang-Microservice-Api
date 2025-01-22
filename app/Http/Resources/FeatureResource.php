<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;


class FeatureResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'owner_id' => $this->owner_id,
            'properties' => new FeaturePropertiesResource($this->whenLoaded('properties')),
            'images' => FeatureImageResource::collection($this->whenLoaded('images')),
            'seller' => $this->whenLoaded('latestTraded', function () {
                return [
                    'id' => $this->latestTraded->seller->id,
                    'code' => $this->latestTraded->seller->code,
                    'name' => $this->latestTraded->seller->name,
                ];
            }),
            'is_hourly_profit_active' => $this->whenLoaded('hourlyProfit', function () {
                return $this->hourlyProfit->is_active;
            }) ?? false,
            'geometry' => $this->whenLoaded('geometry', function () {
                return $this->geometry->coordinates;
            }),
            'construction_status' => $this->whenLoaded('buildingModels', function () {
                return $this->buildingModels->map(function ($buildingModel) {
                    return [
                        'model_id' => $buildingModel->id,
                        'name' => $buildingModel->name,
                        'file' => $buildingModel->file,
                        'images' => $buildingModel->images,
                        'status' => $buildingModel->building->construction_end_date < now() ? 'completed' : 'in progress',
                    ];
                });
            }),

        ];
    }
}
