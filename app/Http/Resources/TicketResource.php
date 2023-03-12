<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\TicketResponseResource;

class TicketResource extends JsonResource
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
            'sender' => $this->sender->name,
            $this->mergeWhen($this->reciever, [
                'reciever' => $this->reciever?->name,
            ]),
            $this->mergeWhen($this->department, [
                'reciever' => $this->department
            ]),
            'code' => $this->code,
            $this->mergeWhen(
                $this->responses
                    && request()->routeIs('tickets.show')
                    || request()->routeIs('tickets.response'),
                [
                    'content' => $this->content,
                    'attachment' => $this->attachment,
                    'responses' => TicketResponseResource::collection($this->responses),
                ]
            ),
            'status' => $this->status,
            'date' => jdate($this->updated_at)->format('Y/m/d'),
            'time' => jdate($this->updated_at)->format('H:m:s'),
        ];
    }
}
