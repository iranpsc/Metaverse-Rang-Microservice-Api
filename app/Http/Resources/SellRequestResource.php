<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SellRequestResource extends JsonResource
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
            'feature_id' => $this->feature_id,
            'seller_id' => $this->seller_id,
            'price_psc' => $this->price_psc,
            'price_irr' => $this->price_irr,
            'status' => $this->status,
        ];
    }
}
