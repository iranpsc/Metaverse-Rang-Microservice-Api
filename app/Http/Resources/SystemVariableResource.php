<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $value
 * @property mixed $slug
 * @property mixed $name
 */
class SystemVariableResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        return [
//            'name' => $this->name,
//            'slug' => $this->slug,
//            'value' => $this->value
        $this->slug => $this->value
        ];
    }
}
