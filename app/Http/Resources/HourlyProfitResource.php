<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class HourlyProfitResource extends JsonResource
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
            'id'         => $this->id,
            'feature_id' => $this->feature_id,
            'feature_properties_id' => $this->feature->properties->id,
            'amount'     => number_format($this->amount, 3),
            'karbari'    => $this->feature->properties->karbari,
            'dead_line'  => Jalalian::forge($this->dead_line)->format('Y/m/d H:m:s')
        ];
    }
}
