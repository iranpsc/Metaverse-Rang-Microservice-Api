<?php

namespace App\Http\Resources;

use App\Models\Reset;
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
            $this->mergeWhen($this->mesasge, [
                'message' => $this->message,
            ]),
            'status' => $this->status,
            'level' => $this->level,
            'details' => $this->details,
            'checkout_days_count' => $this->checkout_days_count,
            'automatic_logout' => $this->automatic_logout,
            'phone_reset_count' => 1 - Reset::resetInfo($this->user, 'phone')->count(),
            'email_reset_count' => 1 - Reset::resetInfo($this->user, 'phone')->count(),
        ];
    }
}
