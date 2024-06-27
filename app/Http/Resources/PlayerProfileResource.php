<?php

namespace App\Http\Resources;

use App\Models\Feature;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\WalletResource;

class PlayerProfileResource extends JsonResource
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
            'name' => $this->getPrivacyStatus('name') ? $this->name : null,
            'code' => $this->getPrivacyStatus('code') ? $this->code : null,
            'score' => $this->getPrivacyStatus('score') ? $this->score : null,
            'registered_at' => $this->getPrivacyStatus('registered_at') ? jdate($this->email_verified_at)->format('Y/m/d') : null,
            'level' => $this->getPrivacyStatus('level') ? $this->level : null,
            'score_percentage_to_next_level' => $this->getPrivacyStatus('level') ? $this->level->getScorePercentageToNextLevel($this->resource) ?? 0 : null,
            'wallet' => new WalletResource($this->wallet),
            'images' => $this->profilePhotos->map(function ($photo) {
                return [
                    'id' => $photo->id,
                    'url' => $photo->url,
                ];
            }),
            'online' => $this->last_seen->diffInMinutes() < 2,
            $this->mergeWhen($this->features->count() > 0, [
                'features' => [
                    $this->mergeWhen($this->getPrivacyStatus('maskoni_features'), [
                        'maskoni' => Feature::whereOwnerId($this->id)->where(function ($query) {
                            $query->select('karbari')
                                ->from('feature_properties')
                                ->whereColumn('features.id', 'feature_properties.feature_id')
                                ->limit(1);
                        }, 'm')->count()
                    ]),
                    $this->mergeWhen($this->getPrivacyStatus('tejari_features'), [
                        'tejari' => Feature::whereOwnerId($this->id)->where(function ($query) {
                            $query->select('karbari')
                                ->from('feature_properties')
                                ->whereColumn('features.id', 'feature_properties.feature_id')
                                ->limit(1);
                        }, 't')->count()
                    ]),
                    $this->mergeWhen($this->getPrivacyStatus('amoozeshi_features'), [
                        'amoozeshi' => Feature::whereOwnerId($this->id)->where(function ($query) {
                            $query->select('karbari')
                                ->from('feature_properties')
                                ->whereColumn('features.id', 'feature_properties.feature_id')
                                ->limit(1);
                        }, 'a')->count()
                    ]),
                ]
            ]),
            'followers' => $this->getPrivacyStatus('followers_count') ? $this->followers->count() : null,
            'following' => $this->getPrivacyStatus('following_count') ? $this->following->count() : null,
        ];
    }

    private function getPrivacyStatus(string $field)
    {
        return $this->privacy->where('name', $field)->pluck('display')->first();
    }
}
