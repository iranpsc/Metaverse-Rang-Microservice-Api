<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;
use App\Http\Resources\FollowResource;

class UserResource extends JsonResource
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
            'id' => (string)$this->id,
            'name' => $this->name,
            'email' => $this->email,
            $this->mergeWhen($this->token, [
                'token' => $this->token,
            ]),
            'unread_notifications' => $this->unreadNotifications->count(),
            'score' => $this->score,
            'phone' => $this->phone,
            'automatic_logout' => $this->settings->automatic_logout,
            'level' => $this->level,
            'birthdate' => $this->verified() ? jdate($this->kyc->birthdate)->format('Y/m/d') : null,
            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->level, $this->score),
            $this->mergeWhen(isset($this->profilePhotos), [
                'profile_photos' => [$this->profilePhotos->last()]
            ]),
            'email_verified_at' => jdate($this->email_verified_at)->format('Y/m/d'),
            'wallet' => new WalletResource($this->wallet),
            'settings' => new SettingResource($this->settings),
            'general_settings' => new NotificationSettingsResource($this->settings->notifcations),
            'notifications' => $this->unreadNotifications,
            'referral_link' => $this->referal_link,
            'code' => $this->code,
            'follows' => [
                'followers' => FollowResource::collection($this->followers()->orderBy('score', 'DESC')->lazy()),
                'following' => FollowResource::collection($this->following),
            ],
            $this->mergeWhen(!empty($this->features), [
                'features' => FeatureResource::collection($this->features),
            ]),
            $this->mergeWhen(!empty($this->kyc), [
                'kyc' => new KycResource($this->kyc),
            ]),

        ];
    }
}
