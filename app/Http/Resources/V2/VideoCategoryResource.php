<?php

namespace App\Http\Resources\V2;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\V2\VideoSubCategoryResource;

class VideoCategoryResource extends JsonResource
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
            'image' => $this->image,
            $this->mergeWhen(request()->routeIs('tutorials.categories.show'), [
                'description' => $this->description,
                'subcategories' => VideoSubcategoryResource::collection($this->subCategories),
            ])
        ];
    }
}
