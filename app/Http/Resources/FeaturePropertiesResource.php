<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class FeaturePropertiesResource extends JsonResource
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
            'address' => $this->address,
            'density' => $this->density,
            'label' => $this->label,
            'karbari' => $this->karbari,
            'area' => $this->area,
            'stability' => $this->stability,
            'region' => $this->region,
            'owner' => $this->owner,
            'rgb' => $this->rgb,
            'price_psc' => $this->price_psc,
            'price_irr' => $this->price_irr,
            'minimum_price_percentage' => $this->minimum_price_percentage,
        ];
    }
}
