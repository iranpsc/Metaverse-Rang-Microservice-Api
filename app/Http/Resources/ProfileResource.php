<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'id' => (string)$this->id,
            'name' => $this->name,
            'code' => $this->code,
            'score' => $this->score,
            'registered_at' => jdate($this->email_verified_at)->format('Y/m/d'),
            'profile_images' => $this->profilePhotos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => $photo->url,
                ];
            }),
            'followers' => $this->followers_count,
            'following' => $this->following_count,
        ];
    }
}
