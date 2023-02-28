<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AssetResource extends JsonResource
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
            'psc' => $this->format_number($this->psc),
            'irr' => $this->format_number($this->irr),
            'red' => $this->format_number($this->red),
            'blue' => $this->format_number($this->blue),
            'yellow' => $this->format_number($this->yellow),
            'satisfaction' => number_format($this->satisfaction, 1),
            'effect' => $this->effect,
        ];
    }
}
