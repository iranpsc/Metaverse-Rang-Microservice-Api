<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
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
            'title' => $this->title,
            'url' => $this->whenNotNull($this->url),
            'subject' => $this->subject,
            'content' => $this->whenNotNull($this->content),
            'attachment' => $this->whenLoaded('image', function () {
                return url('uploads/' . $this->image->url);
            }),
            'attachments' => $this->whenLoaded('images', function () {
                return $this->images->map(function ($image) {
                    return url('uploads/' . $image->url);
                });
            }),
            'datetime' => jdate($this->created_at)->format('Y/m/d H:i:s'),
        ];
    }
}
