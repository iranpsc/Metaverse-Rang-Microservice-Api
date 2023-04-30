<?php

namespace App\Http\Resources;

use App\Models\User;
use App\Models\VideoSubCategory;
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
            'creator_image' => User::firstWhere('code', $this->creator_code)->profilePhotos->last()?->url,
            'video' => $this->fileName,
            'image' => $this->image,
            'views' => $this->views->count(),
            'likes' => $this->interactions->where('liked', 1)->count(),
            'dislikes' => $this->interactions->where('liked', 0)->count(),
            'category_name' => $this->categoriable->category->name,
            'category_slug' => $this->categoriable->category->slug,
            'sub_category_name' => $this->categoriable->name,
            'sub_category_slug' => $this->categoriable->slug,
            'created_at' => jdate($this->created_at)->format('Y/m/d')
        ];
    }
}
