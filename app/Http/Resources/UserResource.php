<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\AssetResource;
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
            'score' => $this->score,
            'phone' => null,
            'automatic_logout' => $this->settings->automatic_logout,
            'level' => $this->level,
            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->level, $this->score),
            $this->mergeWhen(isset($this->profilePhotos), [
                'profile_photos' => [$this->profilePhotos->last()]
            ]),
            'email_verified_at' => Jalalian::forge($this->email_verified_at)->format('Y/m/d'),
            'assets' => new AssetResource($this->assets),
            'settings' => new SettingResource($this->settings),
            'general_settings' => new GeneralSettingsResource($this->generalSettings),
            'notifications' => $this->unreadNotifications,
            'referral_link' => $this->referal_link,
            'code' => $this->code,
            'referals' => $this->referals,
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
