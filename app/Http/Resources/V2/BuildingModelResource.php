<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;

class BuildingModelResource extends JsonResource
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
            'model_id' => $this->model_id,
            'name' => $this->name,
            'sku' => $this->sku,
            'images' => $this->images,
            'attributes' => $this->attributes,
            'file' => $this->file,
            'required_satisfaction' => number_format($this->required_satisfaction, 4),
            'building' => [
                'model_id' => $this->building->model_id,
                'feature_id' => $this->building->feature_id,
                'construction_start_date' => jdate($this->building->construction_start_date)->format('Y/m/d H:i:s'),
                'construction_end_date' => jdate($this->building->construction_end_date)->format('Y/m/d H:i:s'),
                'launched_satisfaction' => number_format($this->building->launched_satisfaction, 4),
                'information' => $this->building->information,
            ],
        ];
    }
}
