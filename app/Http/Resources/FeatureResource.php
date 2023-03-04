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
            $this->mergeUnless(request()->routeIs('home.features'), [
                'id' => $this->id,
                'owner_id' => $this->owner_id,
                'properties' => [
                    'id' => $this->properties->id,
                    'address' => $this->properties->address,
                    'density' => $this->properties->density,
                    'label' => $this->properties->label,
                    'karbari' => $this->properties->karbari,
                    'area' => $this->properties->area,
                    'region' => $this->properties->region,
                    'owner' => $this->properties->owner,
                    'rgb' => $this->properties->rgb,
                    $this->mergeWhen(Auth::guard('sanctum'), [
                        'price_psc' => $this->properties->price_psc,
                        'price_irr' => $this->properties->price_irr,
                        'date' => $this->latestTraded?->created_at,
                        'minimum_price_percentage' => $this->minimum_price_percentage,
                    ])
                ],
                'images' => $this->images,
                $this->mergeWhen($this->latestTraded, [
                    'seller' => [
                        'id' => $this->latestTraded->seller->id ?? "",
                        'name' => $this->latestTraded->seller->name ?? "",
                        'code' => $this->latestTraded->seller->code ?? "",
                    ],
                ]),
            ]),
            $this->mergeWhen(request()->routeIs('home.features'), [
                'geometry' => $this->geometry->coordinates,
            ]),
        ];
    }
}
