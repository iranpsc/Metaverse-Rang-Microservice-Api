<?php

namespace App\Http\Resources;

use App\Helpers\FeatureHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SearchFeatureResultResource extends JsonResource
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
            'id' => $this->feature->id,
            'feature_properties_id' => Str::upper($this->id),
            'address' => $this->address,
            'karbari' => FeatureHelper::getFeatureName($this->feature),
            'price_psc' => $this->price_psc,
            'price_irr' => $this->price_irr,
            'owner_code' => Str::upper($this->feature->owner->code),
        ];
    }
}
