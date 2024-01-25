<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

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
            'description' => $this->when(request()->routeIs('tutorials.index'), Str::limit($this->description, 150), $this->description),
            'creator_code' => $this->creator->code,
            'creator_image' => optional($this->creator->profilePhotos->last())->url,
            'image' => $this->image_url,
            'views' => $this->views_count,
            'likes' => $this->likes,
            'dislikes' => $this->dislikes,
            'category_name' => $this->subCategory->category->name,
            'category_slug' => $this->subCategory->category->slug,
            'sub_category_name' => $this->subCategory->name,
            'sub_category_slug' => $this->subCategory->slug,
            $this->mergeWhen(request()->routeIs('tutorials.show'), [
                'video' => $this->video_url,
                'creator_name' => $this->creator->name,
                'created_at' => jdate($this->created_at)->format('Y/m/d')
            ]),
        ];
    }
}
