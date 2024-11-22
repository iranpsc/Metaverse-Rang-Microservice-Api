<?php

namespace App\Http\Resources\V2\Level;

use Illuminate\Http\Resources\Json\JsonResource;

class PrizeResource extends JsonResource
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
            'level_id' => $this->level_id,
            'psc' => $this->psc,
            'yellow' => $this->yellow,
            'blue' => $this->blue,
            'red' => $this->red,
            'effect' => $this->effect,
            'satisfaction' => number_format($this->satisfaction, 2),
            'created_at' => jdate($this->created_at)->format('Y/m/d H:i:s'),
        ];
    }
}
