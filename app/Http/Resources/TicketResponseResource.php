<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TicketResponseResource extends JsonResource
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
            'ticket_id' => (string)$this->ticket->id,
            'response' => $this->response,
            $this->mergeWhen($this->attachment, [
                'attachment' => $this->attachment,
            ]),
            'responser_id' => $this->responser_id,
            'responser_name' => $this->responser_name,
            'date' => jdate($this->created_at)->format('Y/m/d'),
            'time' => jdate($this->created_at)->format('H:m:s'),
        ];
    }
}
