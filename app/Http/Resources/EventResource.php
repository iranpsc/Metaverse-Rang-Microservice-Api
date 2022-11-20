<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

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
            'content' => $this->content,
            'image' => $this->image->url,
            'start_date' => Jalalian::forge($this->start_date)->format('Y/m/d'),
            'end_date' => Jalalian::forge($this->end_date)->format('Y/m/d'),
            'start_time' => Jalalian::forge($this->start_time)->format('H:m'),
            'end_time' => Jalalian::forge($this->end_time)->format('H:m'),
            'views' => $this->views,
            'likes' => $this->likes?->count(),
            'dislikes' => $this->dislikes?->count(),
            'already_like' => true,
            'already_disliked' => false,
        ];
    }
}
