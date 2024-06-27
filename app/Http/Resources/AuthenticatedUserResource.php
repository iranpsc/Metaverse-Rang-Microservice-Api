<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthenticatedUserResource extends JsonResource
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
            'token' => $this->token,
            'automatic_logout' => $this->settings->automatic_logout ?: 55,
            'code' => $this->code,
            'level' => $this->level,
            'image' => $this->latestProfilePhoto->url ?? [],
            'notifications' => $this->unreadNotifications_count,
            'socre_percentage_to_next_level' => $this->level ? $this->level->getScorePercentageToNextLevel($this->resource) : 0,
            'unasnwered_questions_count' => getUnansweredQuestionsCount($this->resource),
            'hourly_profit_time_percentage' => hourlyProfitInfo($this->resource),
            'verified_kyc' => $this->verified(),
            'birthdate' => $this->verified() ? jdate($this->kyc->birthdate)->format('Y/m/d') : null,
        ];
    }
}
