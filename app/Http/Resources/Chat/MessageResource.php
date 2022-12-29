<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $chat_id
 * @property mixed $user_id
 * @property mixed $message
 * @property mixed $type
 * @property mixed $seen_status
 */
class MessageResource extends JsonResource
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
            'chat_id' => $this->chat_id,
            'user_id' => $this->user_id,
            'message' => $this->message,
            'type' => $this->type,
            'seen_status' => $this->seen_status,
        ];
    }
}
