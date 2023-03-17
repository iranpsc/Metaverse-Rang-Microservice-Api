<?php

namespace App\Http\Resources;

use App\Models\Feature;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileResource extends JsonResource
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
            'name' => $this->name,
            'code' => $this->code,
            'score' => $this->score,
            'registered_at' => jdate($this->email_verified_at)->format('Y/m/d'),
            'level' => $this->level,
            'score_percentage_to_next_level' => $this->level?->getScorePercentageToNextLevel($this->resource) ?? 0,
            'hourly_profit_time_percentage' => hourlyProfitInfo($this->resource),
            'notifications' => $this->unreadNotifications->count(),
            'wallet' => new AssetResource($this->assets),
            'image' => $this->profilePhotos->last()?->url,
            $this->mergeWhen($this->features->count() > 0, [
                'features' => [
                    'maskoni' => Feature::whereOwnerId($this->id)->where(function ($query) {
                        $query->select('karbari')
                            ->from('feature_properties')
                            ->whereColumn('features.id', 'feature_properties.feature_id')
                            ->limit(1);
                    }, 'm')->count(),

                    'tejari' => Feature::whereOwnerId($this->id)->where(function ($query) {
                        $query->select('karbari')
                            ->from('feature_properties')
                            ->whereColumn('features.id', 'feature_properties.feature_id')
                            ->limit(1);
                    }, 't')->count(),

                    'amozeshi' => Feature::whereOwnerId($this->id)->where(function ($query) {
                        $query->select('karbari')
                            ->from('feature_properties')
                            ->whereColumn('features.id', 'feature_properties.feature_id')
                            ->limit(1);
                    }, 'a')->count(),
                ]
            ]),
            'followers' => $this->followers->count(),
            'following' => $this->following->count(),
        ];
    }
}
