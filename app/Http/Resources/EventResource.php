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
            $this->mergeWhen(request()->routeIs('calendar.events.show') || request()->routeIs('calendar.versions.show'), [
                'description' => $this->content,
                'btn_name' => $this->btn_name,
                'btn_link' => $this->btn_link,
                'color' => $this->color,
            ]),
            'image' => $this->image,
            'starts_at' => jdate($this->starts_at)->format('Y/m/d H:i'),
            $this->mergeWhen(!$this->is_version, [
                'ends_at' => jdate($this->ends_at)->format('Y/m/d H:i'),
            ]),
            'views' => $this->views->count(),
            'likes' => $this->likes->count(),
            'dislikes' => $this->dislikes->count(),

            $this->mergeWhen($this->is_version, [
                'version_title' => $this->version_title,
            ])
        ];
    }
}
