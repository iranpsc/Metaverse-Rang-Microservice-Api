<?php

namespace App\Http\Resources;

use App\Models\User;
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
            'description' => $this->description,
            'creator_code' => $this->creator_code,
            'creator_image' => optional(User::firstWhere('code', $this->creator_code)->profilePhotos->last())->url,
            'video' => $this->fileName,
            'image' => $this->image,
            'views' => $this->views->count(),
            'likes' => $this->interactions->where('liked', 1)->count(),
            'dislikes' => $this->interactions->where('liked', 0)->count(),
            'category_name' => $this->subCategory->category->name,
            'category_slug' => $this->subCategory->category->slug,
            'sub_category_name' => $this->subCategory->name,
            'sub_category_slug' => $this->subCategory->slug,
            'created_at' => jdate($this->created_at)->format('Y/m/d')
        ];
    }
}
