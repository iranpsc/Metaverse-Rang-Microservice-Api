<?php

namespace App\Http\Resources\Chat;

use App\Constants\ChatSeenStatus;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class ChatResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'from_user' => $this->from_user,
            'to_user' => $this->to_user,
            'created_at' => Jalalian::forge($this->created_at)->format('Y/m/d'),
            'unseen_messages_count' => $this->messages->where('seen_status', ChatSeenStatus::SENT)->where('user_id', '!=', auth()->user()->id)->count()
        ];
    }
}
