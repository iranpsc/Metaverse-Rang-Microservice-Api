<?php

namespace App\Http\Resources;

use App\Models\Feature;
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
            'online' => $this->last_seen->diffInMinutes(now()) < 2,

            $this->mergeWhen($this->privacy->where('name', 'name')->pluck('display')->first(), [
                'name' => $this->name,
            ]),

            $this->mergeWhen($this->privacy->where('name' , 'email')->pluck('display')->first(),[
                'email' => $this->email,
            ]),

            $this->mergeWhen($this->privacy->where('name' , 'phone')->pluck('display')->first(), [
                'phone' => $this->phone,
            ]),

            'profile_photos' => $this->profilePhotos,

            $this->mergeWhen($this->privacy->where('name' , 'score')->pluck('display')->first(), [
                'score' => $this->score,
            ]),


            $this->mergeWhen($this->privacy->where('name' , 'level')->pluck('display')->first(),[
                'level' => $this->level,
            ]),

            'score_percentage_to_next_level' => getScorePercentageToNextLevel($this->level, $this->score),
            'assets' => new AssetResource($this->assets),
            'referral_link' => $this->referal_link,
            $this->mergeWhen($this->privacy->where('name' , 'code')->pluck('display')->first(),[
                'code' => $this->code,
            ]),

            'follows' => [
                'followers' => FollowResource::collection($this->followers()->orderBy('score', 'DESC')->lazy()),
                'following' => FollowResource::collection($this->following),
            ],
            $this->mergeWhen(!empty($this->features), [
                'features' => FeatureResource::collection(
                    Feature::where('owner_id', $this->id)->with('properties')
                    ->lazy()
                ),
            ]),
            $this->mergeWhen(!empty($this->kyc), [
                'kyc' => new KycResource($this->kyc),
            ]),
        ];
    }
}
