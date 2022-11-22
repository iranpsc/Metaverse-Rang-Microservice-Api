<?php

namespace App\Http\Resources;

use App\Models\Variable;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
            'asset' => $this->asset,
            'amount' => $this->amount,
            'totalPrice' => Variable::getRate($this->asset) * $this->amount,
            'unitPrice' => Variable::getRate($this->asset),
            $this->mergeWhen($this->image, [
                'image' => $this->image?->url
            ]),
        ];
    }
}
