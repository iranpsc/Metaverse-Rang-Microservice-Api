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
            'name' => $this->whenLoaded('kyc', function () {
                return $this->kyc->full_name;
            }) ?? $this->name,
            'automatic_logout' => $this->settings->automatic_logout ?: 55,
            'code' => $this->code,
            'level' => $this->latest_level ? [
                'id' => $this->latest_level->id,
                'name' => $this->latest_level->name,
                'slug' => $this->latest_level->slug,
                'image' => config('app.admin_panel_url') . '/uploads/' . $this->latest_level->image->url,
                'fbx_file' => $this->latest_level->gem->fbx_file,
            ] : null,
            'image' => $this->latestProfilePhoto->url ?? [],
            'unread_notifications_count' => $this->unreadNotifications_count,
            'socre_percentage_to_next_level' => $this->latest_level ? $this->latest_level->getScorePercentageToNextLevel($this->resource) : 0,
            'unasnwered_questions_count' => getUnansweredQuestionsCount($this->resource),
            'hourly_profit_time_percentage' => hourlyProfitInfo($this->resource),
            'verified_kyc' => $this->verified(),
            'birthdate' => $this->verified() ? jdate($this->kyc->birthdate)->format('Y/m/d') : null,
            'has_wallet' => $this->hasConnectedWallet(),
            'wallet_address' => $this->when(
                $request->user()?->id === $this->id,
                $this->wallet_address
            ),
        ];
    }
}
