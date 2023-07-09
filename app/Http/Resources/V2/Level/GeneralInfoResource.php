<?php

namespace App\Http\Resources\V2\Level;

use Illuminate\Http\Resources\Json\JsonResource;

class GeneralInfoResource extends JsonResource
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
            'score' => $this->score,
            'description' => $this->description,
            'rank' => $this->rank,
            'subcategories' => $this->subcategories,
            'persian_font' => $this->persian_font,
            'english_font' => $this->english_font,
            'file_volume' => $this->file_volume,
            'used_colors' => $this->used_colors,
            'points' => $this->points,
            'lines' => $this->lines,
            'has_animation' => $this->has_animation,
            'designer' => $this->designer,
            'model_designer' => $this->model_designer,
            'creation_date' => $this->creation_date,
        ];
    }
}
