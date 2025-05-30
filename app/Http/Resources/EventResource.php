<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->content,
            'starts_at' => jdate($this->starts_at)->format('Y/m/d H:i'),

            $this->mergeWhen(!$this->is_version, [
                'ends_at' => jdate($this->ends_at)->format('Y/m/d H:i'),
                'views' => $this->whenCounted('views'),
                'btn_name' => $this->btn_name,
                'btn_link' => $this->btn_link,
                'color' => $this->color,
                'image' => $this->whenNotNull('image'),
                'likes' => $this->whenCounted('likes'),
                'dislikes' => $this->whenCounted('dislikes'),
                'user_interaction' => $this->whenLoaded(
                    'userInteraction',
                    function () {
                        return [
                            'has_liked' => $this->userInteraction && $this->userInteraction->liked === 1,
                            'has_disliked' => $this->userInteraction && $this->userInteraction->liked === 0,
                        ];
                    }
                )
            ]),

            $this->mergeWhen($this->is_version, [
                'version_title' => $this->version_title,
            ])
        ];
    }
}
