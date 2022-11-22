<?php

namespace App\Http\Resources;

use App\Models\Feature;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class TopPlayerResource extends JsonResource
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
            'online' => Carbon::parse($this->last_seen)->diffInMinutes(now()) > 2 ? false : true,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile-photo' => $this->profilePhoto->url ?? "",
            'score' => $this->score,
            'level' => $this->level ?? null,
            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->level, $this->score),
            'assets' => new AssetResource($this->assets),
            'referral_link' => $this->referal_link,
            'code' => $this->code,
            'referals' => $this->referals,
            'follows' => [
                'followers' => FollowResource::collection($this->followers()->orderBy('score', 'DESC')->lazy()),
                'following' => FollowResource::collection($this->following),
            ],
            $this->mergeWhen(!empty($this->features), [
                'features' => FeatureResource::collection(
                    Feature::where('owner_id', $this->id)->with('properties', 'geometry.coordinates')
                    ->lazy()
                ),
            ]),
            $this->mergeWhen(!empty($this->kyc), [
                'kyc' => new KycResource($this->kyc),
            ]),
        ];
    }
}
