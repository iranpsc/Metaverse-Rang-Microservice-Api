<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class LatestTransactionResource extends JsonResource
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
            'id' => $this->latestTransaction->id,
            $this->mergeWhen($this->latestTransaction->status === 1, [
                'ref_id' => $this->latestPayment?->ref_id,
                'date' => Jalalian::forge($this->latestPayment?->created_at)->format('Y/m/d'),
                'hour' => Jalalian::forge($this->latestPayment?->created_at)->format('H:m:s'),
            ]),
            'product' => $this->latestOrder->asset,
            'count' => $this->latestOrder->amount,
            'amount' => $this->latestTransaction->amount,
            'status' => $this->latestTransaction->status,
        ];

    }
}
