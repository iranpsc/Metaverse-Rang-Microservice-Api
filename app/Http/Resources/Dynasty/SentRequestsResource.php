<?php

namespace App\Http\Resources\Dynasty;

use App\Models\Variable;
use Illuminate\Http\Resources\Json\JsonResource;

class SentRequestsResource extends JsonResource
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
            'to_user' => $this->whenLoaded('toUser', function () {
                return [
                    'id' => $this->toUser->id,
                    'code' => $this->toUser->code,
                    'name' => $this->toUser->name,
                    'profile_photo' => $this->toUser->latestProfilePhoto?->url,
                ];
            }),
            'status' => $this->status,
            'relationship' => $this->getRelationShipTitle(),
            'date' => jdate($this->created_at)->format('Y/m/d'),
            'time' => jdate($this->created_at)->format('H:i'),
            'prize' => $this->whenLoaded('requestPrize', function () {
                return [
                    'id' => $this->requestPrize->id,
                    'psc' => number_format($this->requestPrize->psc / Variable::getRate('psc'), 2),
                    'satisfaction' => number_format($this->requestPrize->satisfaction * 100),
                    'introducation_profit_increase' => number_format($this->requestPrize->introducation_profit_increase * 100),
                    'accumulated_capital_reserve' => number_format($this->requestPrize->accumulated_capital_reserve * 100),
                    'data_storage' => number_format($this->requestPrize->data_storage * 100),
                ];
            }),
            $this->mergeWhen(request()->routeIs('dynasty.requests.sent.show'), [
                'message' => $this->message,
            ]),
        ];
    }
}
