<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserFeatureResource extends JsonResource
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
            'properties' => [
                'id' => $this->properties->id,
                'address' => $this->properties->address,
                'density' => $this->properties->density,
                'karbari' => $this->properties->karbari,
                'area' => $this->properties->area,
                'region' => $this->properties->region,
                'rgb' => $this->properties->rgb,
                'price_psc' => $this->properties->price_psc,
                'price_irr' => $this->properties->price_irr,
            ],

            $this->mergeWhen(request()->routeIs('my-features.show') || request()->routeIs('players.feature'), [
                'images' => $this->images,
                $this->mergeWhen($this->latestTraded, [
                    'seller' => [
                        'id' => $this->latestTraded->seller->id ?? "",
                        'name' => $this->latestTraded->seller->name ?? "",
                        'code' => $this->latestTraded->seller->code ?? "",
                    ],
                ]),
            ]),

            $this->mergeWhen(request()->routeIs('my-features.index') || request()->routeIs('players.features'), [
                'geometry' => $this->geometry->coordinates,
            ]),

        ];
    }
}
