<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class VideoTutorialResource extends JsonResource
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
            'slug' => $this->slug,
            'image_url' => $this->image_url,
            'description' => $this->description,
            'views_count' => $this->whenCounted('views'),
            'likes_count' => $this->whenCounted('likes'),
            'dislikes_count' => $this->whenCounted('dislikes'),
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'name' => $this->creator->name,
                    'code' => $this->creator->code,
                    'image' => optional($this->creator->profilePhotos->first())->url,
                ];
            }),
            'category' => $this->whenLoaded('subCategory', function () {
                return [
                    'name' => $this->subCategory->category->name,
                    'slug' => $this->subCategory->category->slug
                ];
            }),
            'sub_category' => $this->whenLoaded('subCategory', function () {
                return [
                    'name' => $this->subCategory->name,
                    'slug' => $this->subCategory->slug
                ];
            }),
            'video_url' => $this->video_url,
            'created_at' => jdate($this->created_at)->format('Y/m/d')
        ];
    }
}
