<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
            $this->mergeWhen(! empty($this->message), [
                'message' => $this->message,
            ]),
            'id' => $this->id,
            'map_id' => $this->map_id,
            'owner_id' => $this->owner_id,
            $this->mergeWhen($this->latestTraded, [
                'seller' => [
                    'id' => $this->latestTraded->seller->id ?? "",
                    'name' => $this->latestTraded->seller->name ?? "",
                    'code' => $this->latestTraded->seller->code ?? "",
                ],
            ]),
            'type' => $this->type,
            'properties' => [
                'id' => $this->properties->id,
                'address' => $this->properties->address,
                'feature_id' => $this->properties->feature_id,
                'density' => $this->properties->density,
                'date' => Jalalian::forge($this->properties->date)->format('Y/m/d'),
                'stability' => $this->properties->stability,
                'label' => $this->properties->label,
                'area' => $this->properties->area,
                'region' => $this->properties->region,
                'karbari' => $this->properties->karbari,
                'owner' => $this->properties->owner,
                'rgb' => $this->properties->rgb,
                'price_psc' => $this->properties->price_psc,
                'price_irr' => $this->properties->price_irr,
            ],
            'geometry' => $this->geometry->load('coordinates'),
            'images' => $this->images,
        ];
    }
}
