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
            $this->mergeUnless(request()->routeIs('home.features'), [
                'id' => $this->id,
                'owner_id' => $this->owner_id,
                'properties' => [
                    'id' => $this->properties->id,
                    'address' => $this->properties->address,
                    'feature_id' => $this->properties->feature_id,
                    'density' => $this->properties->density,
                    'stability' => $this->properties->stability,
                    'label' => $this->properties->label,
                    'area' => $this->properties->area,
                    'region' => $this->properties->region,
                    'karbari' => $this->properties->karbari,
                    'owner' => $this->properties->owner,
                    'rgb' => $this->properties->rgb,
                    'price_psc' => $this->properties->price_psc,
                    'price_irr' => $this->properties->price_irr,
                    'date' => $this->latestTraded->created_at ?? null,
                ],
                'images' => $this->images,
                $this->mergeWhen($this->latestTraded, [
                    'seller' => [
                        'id' => $this->latestTraded->seller->id ?? "",
                        'name' => $this->latestTraded->seller->name ?? "",
                        'code' => $this->latestTraded->seller->code ?? "",
                    ],
                ]),
                $this->mergeWhen($this->hourlyProfit, [
                    'hourly_profit' => [
                        'asset' => $this->hourlyProfit?->asset,
                        'amount' => $this->hourlyProfit?->amount,
                        'deadline_date' => Jalalian::forge($this->hourlyProfit?->dead_line)->format('Y/m/d'),
                        'deadline_time' => Jalalian::forge($this->hourlyProfit?->dead_line)->format('H:m:s'),
                    ]
                ]),
            ]),
            $this->mergeWhen(request()->routeIs('home.features'), [
                'geometry' => $this->geometry->coordinates,
            ]),
        ];
    }
}
