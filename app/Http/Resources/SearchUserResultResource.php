<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

class SearchUserResultResource extends JsonResource
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
            'code' => Str::upper($this->code),
            'name' => $this->name,
            'followers' => format_number($this->followers->count()),
            'level' => $this->level?->name,
            'photo' => $this->profilePhotos->last()?->url,
        ];
    }
}
