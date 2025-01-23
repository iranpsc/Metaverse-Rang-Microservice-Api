<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BuyRequestResource extends JsonResource
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
            'buyer' => $this->whenLoaded('buyer', function() {
                return [
                    'id' => $this->buyer->id,
                    'code' => $this->buyer->code,
                ];
            }),
            'seller' => $this->whenLoaded('seller', function() {
                return [
                    'id' => $this->seller->id,
                    'code' => $this->seller->code,
                ];
            }),
            'feature_id' => $this->feature_id,
            'status' => $this->status,
            'note' => $this->note,
            'price_psc' => $this->price_psc,
            'price_irr' => $this->price_irr,
            'feature_properties' => new FeaturePropertiesResource($this->whenLoaded('feature.properties')),
            'feature_coordinates' => CoordinatesResource::collection($this->whenLoaded('feature.coordinates')),
            'created_at' => jdate($this->created_at)->format('Y/m/d'),
            'requested_grace_period' => jdate($this->requested_grace_period)->format('Y/m/d H:i:s'),
        ];
    }
}
