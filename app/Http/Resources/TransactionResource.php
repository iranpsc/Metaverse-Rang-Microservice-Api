<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
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
            'id'     => $this->id,
            'type'   => $this->type,
            'asset'  => $this->asset,
            'amount' => $this->amount,
            'action' => $this->action,
            'status' => $this->status,
            'date'   => jdate($this->created_at)->format('Y/m/d'),
            'time'   => jdate($this->created_at)->format('H:m:s'),
        ];
    }
}
