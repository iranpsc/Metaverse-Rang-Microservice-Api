<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V2\VideoCategoryResource;
use App\Http\Resources\VideoTutorialResource;

class VideoSubCategoryResource extends JsonResource
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
            'slug' => $this->slug,
            'image' => config('app.admin_panel_url') . '/uploads/' . $this->image,
            $this->mergeWhen(request()->routeIs('tutorials.subcategories.show'), [
                'description' => $this->description,
                'videos' => VideoTutorialResource::collection($this->videos)
            ])
        ];
    }
}
