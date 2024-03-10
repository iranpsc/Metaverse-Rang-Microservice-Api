<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BankAccountResource extends JsonResource
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
            'bank_name' => $this->bank_name,
            'shaba_num' => $this->shaba_num,
            'card_num'  => $this->card_num,
            'status' => $this->status,
            'errors' => $this->whenNotNull($this->errors),
        ];
    }
}
