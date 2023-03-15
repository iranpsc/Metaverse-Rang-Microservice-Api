<?php

namespace App\Http\Resources\Dynasty;

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
            'to_user' => [
                'id' => $this->toUser->id,
                'code' => $this->toUser->code,
                'name' => $this->toUser->name,
            ],
            'status' => $this->status,
            'relationship' => $this->getRelationShipTitle(),
            $this->mergeWhen(request()->routeIs('dynasty.requests.sent.show'), [
                'message' => $this->message,
            ]),
        ];
    }
}
