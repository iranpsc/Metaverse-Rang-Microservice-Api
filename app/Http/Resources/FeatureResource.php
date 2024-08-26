<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;


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
            'properties' => [
                'id' => $this->properties->id,
                'address' => $this->properties->address,
                'density' => $this->properties->density,
                'label' => $this->properties->label,
                'karbari' => $this->properties->karbari,
                'area' => $this->properties->area,
                'stability' => $this->properties->stability,
                'region' => $this->properties->region,
                'owner' => $this->properties->owner,
                'rgb' => $this->properties->rgb,
                $this->mergeWhen(Auth::check(), [
                    'price_psc' => $this->properties->price_psc,
                    'price_irr' => $this->properties->price_irr,
                    'date' => $this->latestTraded?->created_at,
                    'minimum_price_percentage' => $this->properties->minimum_price_percentage,
                ])
            ],
            'images' => $this->images?->map(function ($image) {
                return [
                    'id' => $image->id,
                    'url' => $image->url,
                ];
            }),
            $this->mergeWhen($this->latestTraded, [
                'seller' => [
                    'id' => $this->latestTraded->seller->id ?? "",
                    'code' => $this->latestTraded->seller->code ?? "",
                ],
            ]),
            $this->mergeWhen(!is_null($this->hourlyProfit), [
                'is_hourly_profit_active' => $this->hourlyProfit?->is_active,
            ]),
            'geometry' => $this->geometry->coordinates,
        ];
    }
}
