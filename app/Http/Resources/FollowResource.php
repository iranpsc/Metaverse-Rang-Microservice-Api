<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class FollowResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'score' => $this->score,
            'code' =>
            $this->mergeWhen(isset($this->profilePhotos), [
                'image' => $this->profilePhotos->url ?? "",
            ]),
            'followed_at' => Jalalian::forge($this->created_at)->format('Y/m/d')
        ];
    }
}
