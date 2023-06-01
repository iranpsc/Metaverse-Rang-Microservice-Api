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
            'user_id'    => $this->user_id,
            'feature_db_id' => $this->feature->id,
            'feature_id' => $this->feature->properties->id,
            'amount'     => number_format($this->amount, 3),
            'karbari'    => $this->feature->properties->karbari,
            'dead_line'  => jdate($this->dead_line)->format('Y/m/d')
        ];
    }
}
