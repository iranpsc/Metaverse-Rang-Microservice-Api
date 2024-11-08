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
            'url' => $this->url,
            'subject' => $this->subject,
            'content' => $this->whenNotNull($this->content, function () {
                return $this->content;
            }),
            'attachment' => $this->whenLoaded('image', function () {
                return $this->image->url;
            }),
            'date' => jdate($this->created_at)->format('Y/m/d'),
        ];
    }
}
