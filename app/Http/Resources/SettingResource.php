<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
            'message' => $this->message ?? '',
            'status' => $this->status,
            'level' => $this->level,
            'details' => $this->details,
            'checkout_days_count' => $this->checkout_days_count,
            'automatic_logout' => $this->automatic_logout,
            'phone_reset_count' => 1 - ($this->user->resetPhone->count ?? 0),
            'email_reset_count' => 1 - ($this->user->resetEmail->count ?? 0),
        ];
    }
}
