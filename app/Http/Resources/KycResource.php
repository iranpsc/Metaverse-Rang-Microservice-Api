<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KycResource extends JsonResource
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
            'melli_card' => $this->melli_card,
            'fname' => $this->fname,
            'lname' => $this->lname,
            'melli_code' => $this->melli_code,
            'birthdate' => jdate($this->birthdate)->format('Y/m/d'),
            'province' => $this->province,
            'status' => $this->status,
            'video' => $this->video,
            'errors' => $this->whenNotNull($this->errors),
        ];
    }
}
